<?php
/** Atualizações pelo mecanismo nativo do WordPress e Releases do GitHub. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Site_Turoriais_GitHub_Updater {
    const REPO = 'https://github.com/zemariopassos/site-turoriais';
    const API = 'https://api.github.com/repos/zemariopassos/site-turoriais';
    const CACHE = 'site_turoriais_github_release_v1';
    private $file;

    public function __construct( $file ) {
        $this->file = plugin_basename( $file );
        add_filter( 'update_plugins_github.com', array( $this, 'update' ), 10, 4 );
        add_filter( 'plugins_api', array( $this, 'info' ), 10, 3 );
        add_filter( 'upgrader_pre_download', array( $this, 'download' ), 10, 4 );
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_site_turoriais_check_updates', array( $this, 'check' ) );
    }

    private function headers( $binary = false ) {
        $headers = array( 'Accept' => $binary ? 'application/octet-stream' : 'application/vnd.github+json', 'User-Agent' => 'site-turoriais/' . SITE_TURORIAIS_VERSION );
        if ( defined( 'SITE_TURORIAIS_GITHUB_TOKEN' ) && is_string( SITE_TURORIAIS_GITHUB_TOKEN ) && SITE_TURORIAIS_GITHUB_TOKEN ) {
            $headers['Authorization'] = 'Bearer ' . SITE_TURORIAIS_GITHUB_TOKEN;
        }
        return $headers;
    }

    /** Aceita apenas versões estáveis e o ZIP de instalação anexado à release. */
    public static function parse_release( $data ) {
        if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) || empty( $data['tag_name'] ) || ! is_string( $data['tag_name'] ) || ! preg_match( '/^v?(\d+\.\d+\.\d+)$/D', $data['tag_name'], $match ) ) {
            return false;
        }
        foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
            if ( ! is_array( $asset ) || 'site-turoriais.zip' !== ( $asset['name'] ?? '' ) || 'uploaded' !== ( $asset['state'] ?? '' ) || empty( $asset['id'] ) || ! ctype_digit( (string) $asset['id'] ) ) {
                continue;
            }
            return array( 'version' => $match[1], 'package' => self::API . '/releases/assets/' . $asset['id'], 'digest' => isset( $asset['digest'] ) && is_string( $asset['digest'] ) && preg_match( '/^sha256:[a-f0-9]{64}$/D', $asset['digest'] ) ? substr( $asset['digest'], 7 ) : '', 'notes' => is_string( $data['body'] ?? null ) ? $data['body'] : '' );
        }
        return false;
    }

    private function release() {
        $cached = get_transient( self::CACHE );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        $response = wp_remote_get( self::API . '/releases/latest', array( 'headers' => $this->headers(), 'timeout' => 15, 'redirection' => 0 ) );
        $release = false;
        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $release = self::parse_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
        }
        if ( ! $release ) {
            $release = array( 'error' => 'Não foi possível consultar uma versão estável com o ZIP site-turoriais.zip. Verifique a conexão, a release e, se o repositório for privado, o token de leitura no wp-config.php.' );
        }
        set_transient( self::CACHE, $release, isset( $release['error'] ) ? HOUR_IN_SECONDS : 6 * HOUR_IN_SECONDS );
        return $release;
    }

    public function update( $update, $plugin_data, $plugin_file, $locales ) {
        if ( $plugin_file !== $this->file ) {
            return $update;
        }
        $release = $this->release();
        if ( isset( $release['error'] ) ) {
            return $update;
        }
        return array( 'id' => self::REPO, 'slug' => 'site-turoriais', 'version' => $release['version'], 'url' => self::REPO, 'package' => $release['package'], 'requires' => '6.0', 'requires_php' => '7.4' );
    }

    public function info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || 'site-turoriais' !== ( $args->slug ?? '' ) ) {
            return $result;
        }
        $release = $this->release();
        if ( isset( $release['error'] ) ) {
            return $result;
        }
        return (object) array( 'name' => 'site-turoriais', 'slug' => 'site-turoriais', 'version' => $release['version'], 'homepage' => self::REPO, 'requires' => '6.0', 'requires_php' => '7.4', 'download_link' => $release['package'], 'sections' => array( 'description' => 'Tutoriais no painel com MP4 e playlists do YouTube.', 'changelog' => wpautop( esc_html( $release['notes'] ) ) ) );
    }

    /** O token é enviado somente à API fixa. Redirecionamentos baixam sem credenciais. */
    public function download( $reply, $package, $upgrader, $hook_extra ) {
        if ( false !== $reply || ! is_string( $package ) || ! preg_match( '~^' . preg_quote( self::API, '~' ) . '/releases/assets/[0-9]+$~D', $package ) ) {
            return $reply;
        }
        $release = $this->release();
        if ( isset( $release['error'] ) || $release['package'] !== $package ) {
            return new WP_Error( 'site_turoriais_release_changed', 'A versão no GitHub mudou. Verifique as atualizações novamente antes de instalar.' );
        }
        $temp = wp_tempnam( 'site-turoriais.zip' );
        if ( ! $temp ) {
            return new WP_Error( 'site_turoriais_temp', 'Não foi possível criar o arquivo temporário.' );
        }
        $response = wp_remote_get( $package, array( 'headers' => $this->headers( true ), 'timeout' => 60, 'redirection' => 0, 'stream' => true, 'filename' => $temp ) );
        $code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
        if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
            $location = wp_remote_retrieve_header( $response, 'location' );
            wp_delete_file( $temp );
            if ( 'https' !== wp_parse_url( $location, PHP_URL_SCHEME ) || ! in_array( wp_parse_url( $location, PHP_URL_HOST ), array( 'release-assets.githubusercontent.com', 'objects.githubusercontent.com' ), true ) ) {
                return new WP_Error( 'site_turoriais_redirect', 'O GitHub retornou um endereço de download inesperado.' );
            }
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $temp = download_url( $location, 120 );
            if ( is_wp_error( $temp ) ) {
                return $temp;
            }
        } elseif ( 200 !== $code ) {
            wp_delete_file( $temp );
            return new WP_Error( 'site_turoriais_download', 'Não foi possível baixar o plugin do GitHub. Confira o acesso ao repositório e tente novamente.' );
        }
        if ( $release['digest'] && ! hash_equals( $release['digest'], hash_file( 'sha256', $temp ) ) ) {
            wp_delete_file( $temp );
            return new WP_Error( 'site_turoriais_checksum', 'O arquivo baixado não corresponde ao checksum publicado no GitHub.' );
        }
        return $temp;
    }

    public function menu() {
        add_submenu_page( 'flaneur-tutoriais', 'Atualizações', 'Atualizações', 'manage_options', 'site-turoriais-updates', array( $this, 'page' ) );
    }

    public function check() {
        if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'update_plugins' ) ) {
            wp_die( 'Você não tem permissão para verificar atualizações.' );
        }
        check_admin_referer( 'site_turoriais_check_updates' );
        delete_transient( self::CACHE );
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();
        wp_safe_redirect( admin_url( 'admin.php?page=site-turoriais-updates' ) );
        exit;
    }

    public function page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Você não tem permissão para acessar esta página.' );
        }
        $release = $this->release();
        echo '<div class="wrap"><h1>Atualizações do site-turoriais</h1><p>Versão instalada: <strong>' . esc_html( SITE_TURORIAIS_VERSION ) . '</strong></p>';
        if ( isset( $release['error'] ) ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html( $release['error'] ) . '</p></div>';
        } else {
            echo '<p>Última versão disponível: <strong>' . esc_html( $release['version'] ) . '</strong></p><p>' . ( version_compare( $release['version'], SITE_TURORIAIS_VERSION, '>' ) ? 'Há uma atualização disponível. Abra Plugins para atualizar.' : 'Seu plugin está atualizado.' ) . '</p>';
        }
        echo '<p>O WordPress verifica as versões periodicamente. Você decide quando instalar pelo painel de Plugins.</p><p>Se o repositório estiver privado, configure SITE_TURORIAIS_GITHUB_TOKEN no wp-config.php com um token de leitura restrito a este repositório. Não coloque tokens nos arquivos do plugin.</p>';
        if ( current_user_can( 'update_plugins' ) ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="site_turoriais_check_updates">';
            wp_nonce_field( 'site_turoriais_check_updates' );
            submit_button( 'Verificar atualizações agora', 'secondary' );
            echo '</form><p><a class="button button-primary" href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Abrir Plugins</a></p>';
        }
        echo '</div>';
    }
}
