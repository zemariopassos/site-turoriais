<?php
/**
 * Plugin Name: site-turoriais
 * Description: Biblioteca de tutoriais no painel com vídeos MP4 e playlists automáticas do YouTube. Independente do tema.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: site-turoriais
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Biblioteca de videos exclusiva do painel administrativo. */
function site_turoriais_register_tutorials() {
    $capabilities = array_fill_keys(
        array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
            'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts',
            'delete_published_posts', 'delete_others_posts', 'edit_private_posts',
            'edit_published_posts', 'create_posts' ),
        'manage_options'
    );
    $capabilities['read'] = 'read';

    register_post_type( 'tutoriais', array(
        'labels' => array(
            'name' => 'Tutoriais', 'singular_name' => 'Tutorial',
            'add_new' => 'Adicionar tutorial', 'add_new_item' => 'Adicionar tutorial',
            'edit_item' => 'Editar tutorial', 'all_items' => 'Gerenciar tutoriais',
            'search_items' => 'Buscar tutoriais', 'not_found' => 'Nenhum tutorial encontrado.',
        ),
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_admin_bar' => false,
        'show_in_nav_menus' => false,
        'show_in_rest' => false,
        'rewrite' => false,
        'query_var' => false,
        'has_archive' => false,
        'supports' => array( 'title', 'editor', 'page-attributes' ),
        'capabilities' => $capabilities,
        'map_meta_cap' => false,
    ) );
}
add_action( 'init', 'site_turoriais_register_tutorials' );

function site_turoriais_tutorials_menu() {
    add_menu_page( 'Tutoriais', 'Tutoriais', 'read', 'flaneur-tutoriais', 'site_turoriais_tutorials_library', 'dashicons-video-alt3', 25 );
    add_submenu_page( 'flaneur-tutoriais', 'Assistir tutoriais', 'Assistir tutoriais', 'read', 'flaneur-tutoriais', 'site_turoriais_tutorials_library' );
    add_submenu_page( 'flaneur-tutoriais', 'Gerenciar tutoriais', 'Gerenciar tutoriais', 'manage_options', 'edit.php?post_type=tutoriais' );
    add_submenu_page( 'flaneur-tutoriais', 'Playlist do YouTube', 'Playlist do YouTube', 'manage_options', 'site-turoriais-playlist', 'site_turoriais_settings_page' );
    add_submenu_page( 'flaneur-tutoriais', 'Adicionar tutorial', 'Adicionar tutorial', 'manage_options', 'post-new.php?post_type=tutoriais' );
}
add_action( 'admin_menu', 'site_turoriais_tutorials_menu' );

function site_turoriais_tutorials_meta_box() {
    add_meta_box( 'flaneur-tutorial-video', 'Vídeo do tutorial', 'site_turoriais_tutorial_video_field', 'tutoriais', 'normal', 'high' );
}
add_action( 'add_meta_boxes_tutoriais', 'site_turoriais_tutorials_meta_box' );

function site_turoriais_tutorial_video_field( $post ) {
    wp_nonce_field( 'flaneur_tutorial_video', 'flaneur_tutorial_nonce' );
    ?>
    <p><label for="flaneur-tutorial-video-url">Selecione um vídeo da Biblioteca de Mídia ou cole um link do YouTube/Vimeo.</label></p>
    <input type="url" class="widefat" id="flaneur-tutorial-video-url" name="flaneur_tutorial_video_url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_flaneur_tutorial_video_url', true ) ); ?>" placeholder="https://…">
    <p><button type="button" class="button" id="flaneur-tutorial-select">Enviar ou selecionar vídeo</button>
    <button type="button" class="button" id="flaneur-tutorial-clear">Remover vídeo</button></p>
    <p class="description">Use o editor para escrever as instruções da aula. Em Atributos, defina a ordem de exibição. Publique para liberar o tutorial aos usuários.</p>
    <?php
}

function site_turoriais_save_tutorial_video( $post_id ) {
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( ! isset( $_POST['flaneur_tutorial_nonce'] ) || ! is_string( $_POST['flaneur_tutorial_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flaneur_tutorial_nonce'] ) ), 'flaneur_tutorial_video' ) ) {
        return;
    }
    if ( ! isset( $_POST['flaneur_tutorial_video_url'] ) || ! is_string( $_POST['flaneur_tutorial_video_url'] ) ) {
        return;
    }
    $url = esc_url_raw( trim( wp_unslash( $_POST['flaneur_tutorial_video_url'] ) ), array( 'http', 'https' ) );
    if ( $url ) {
        update_post_meta( $post_id, '_flaneur_tutorial_video_url', $url );
    } else {
        delete_post_meta( $post_id, '_flaneur_tutorial_video_url' );
    }
}
add_action( 'save_post_tutoriais', 'site_turoriais_save_tutorial_video' );

function site_turoriais_tutorials_assets() {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    if ( 'toplevel_page_flaneur-tutoriais' === $screen->id ) {
        wp_enqueue_style( 'site-turoriais', plugins_url( 'assets/tutoriais-admin.css', __FILE__ ), array(), '1.0.0' );
    }
    if ( 'tutoriais' === $screen->post_type && 'post' === $screen->base && current_user_can( 'manage_options' ) ) {
        wp_enqueue_media();
        wp_enqueue_script( 'site-turoriais', plugins_url( 'assets/tutoriais-admin.js', __FILE__ ), array( 'jquery', 'media-views' ), '1.0.0', true );
    }
}
add_action( 'admin_enqueue_scripts', 'site_turoriais_tutorials_assets' );

/** Resolve somente links do YouTube com uma playlist explícita. */
function site_turoriais_playlist_id( $url ) {
    if ( ! is_string( $url ) ) {
        return '';
    }
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    $scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be', 'www.youtube-nocookie.com', 'youtube-nocookie.com' ), true ) ) {
        return '';
    }
    parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
    return isset( $query['list'] ) && is_string( $query['list'] ) && preg_match( '/^[A-Za-z0-9_-]{10,100}$/D', $query['list'] ) ? $query['list'] : '';
}

function site_turoriais_sanitize_playlist( $value ) {
    $value = is_string( $value ) ? trim( $value ) : '';
    if ( '' === $value ) {
        return '';
    }
    $id = site_turoriais_playlist_id( $value );
    if ( ! $id ) {
        add_settings_error( 'site_turoriais_playlist_url', 'invalid_playlist', 'Cole o link de uma playlist do YouTube (com list=...). O endereço do canal sozinho não identifica a playlist de tutoriais.' );
        return get_option( 'site_turoriais_playlist_url', '' );
    }
    return 'https://www.youtube.com/playlist?list=' . $id;
}

function site_turoriais_register_settings() {
    register_setting( 'site_turoriais_settings', 'site_turoriais_playlist_url', array(
        'type' => 'string', 'sanitize_callback' => 'site_turoriais_sanitize_playlist', 'default' => '', 'show_in_rest' => false,
    ) );
}
add_action( 'admin_init', 'site_turoriais_register_settings' );

function site_turoriais_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Você não tem permissão para configurar os tutoriais.' );
    }
    ?>
    <div class="wrap">
        <h1>Playlist de tutoriais do YouTube</h1>
        <?php settings_errors(); ?>
        <p>No YouTube, abra a playlist de tutoriais do seu canal e copie o endereço dela. Não é necessário cadastrar os vídeos individualmente nem configurar uma chave de API.</p>
        <form action="options.php" method="post">
            <?php settings_fields( 'site_turoriais_settings' ); ?>
            <table class="form-table" role="presentation"><tr>
                <th scope="row"><label for="site-turoriais-playlist-url">Link da playlist</label></th>
                <td><input type="url" class="large-text" id="site-turoriais-playlist-url" name="site_turoriais_playlist_url" value="<?php echo esc_attr( get_option( 'site_turoriais_playlist_url', '' ) ); ?>" placeholder="https://www.youtube.com/playlist?list=PL...">
                <p class="description">Use uma playlist pública ou não listada, com vídeos que permitam incorporação. Playlists privadas não funcionam.</p>
                <p class="description">Os vídeos aparecem no player da playlist, na ordem definida no YouTube. Ao adicionar ou remover aulas no YouTube, a lista será atualizada quando o usuário abrir ou recarregar a página de tutoriais, após a atualização do próprio YouTube.</p>
                <p class="description">Deixe vazio para usar somente os vídeos cadastrados no WordPress. Você também pode usar playlist e MP4 juntos.</p></td>
            </tr></table>
            <?php submit_button( 'Salvar playlist' ); ?>
        </form>
        <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=flaneur-tutoriais' ) ); ?>">Assistir aos tutoriais</a></p>
    </div>
    <?php
}

function site_turoriais_render_playlist() {
    $id = site_turoriais_playlist_id( get_option( 'site_turoriais_playlist_url', '' ) );
    if ( ! $id ) {
        return;
    }
    $src = add_query_arg( array( 'listType' => 'playlist', 'list' => $id, 'rel' => '0' ), 'https://www.youtube-nocookie.com/embed/videoseries' );
    ?>
    <section class="tutorial-playlist" aria-labelledby="site-turoriais-playlist-title">
        <h2 id="site-turoriais-playlist-title">Tutoriais do YouTube</h2>
        <p>Use o botão de lista no player para escolher uma aula da playlist.</p>
        <div class="tutorial-player"><iframe class="tutorial-embed" src="<?php echo esc_url( $src ); ?>" title="Playlist de tutoriais do site" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe></div>
        <p><a href="<?php echo esc_url( 'https://www.youtube.com/playlist?list=' . $id ); ?>" target="_blank" rel="noopener noreferrer">Ver todas as aulas no YouTube</a></p>
    </section>
    <?php
}

function site_turoriais_dashboard_widget() {
    if ( current_user_can( 'read' ) ) {
        wp_add_dashboard_widget( 'site_turoriais_library', 'Tutoriais do site', 'site_turoriais_dashboard_content' );
    }
}
add_action( 'wp_dashboard_setup', 'site_turoriais_dashboard_widget' );

function site_turoriais_dashboard_content() {
    echo '<p>Aprenda a operar o site com nossas aulas em vídeo.</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=flaneur-tutoriais' ) ) . '">Assistir aos tutoriais</a></p>';
}

/** Aceita apenas provedores conhecidos para iframes; arquivos usam o player nativo. */
function site_turoriais_tutorial_player( $url, $title ) {
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    $path = (string) wp_parse_url( $url, PHP_URL_PATH );
    $embed = '';
    if ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'www.youtube-nocookie.com', 'youtube-nocookie.com' ), true ) ) {
        parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
        $id = in_array( $host, array( 'youtu.be', 'www.youtu.be' ), true ) ? trim( $path, '/' ) : ( $query['v'] ?? '' );
        if ( preg_match( '~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})/?$~', $path, $match ) ) {
            $id = $match[1];
        }
        if ( is_string( $id ) && preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ) {
            $embed = 'https://www.youtube-nocookie.com/embed/' . $id;
        }
    } elseif ( in_array( $host, array( 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com' ), true ) && preg_match( '~^/(?:video/)?([0-9]+)(?:/([a-zA-Z0-9]+))?/?$~', $path, $match ) ) {
        $embed = 'https://player.vimeo.com/video/' . $match[1];
        parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
        $hash = $match[2] ?? ( $query['h'] ?? '' );
        if ( is_string( $hash ) && preg_match( '/^[a-zA-Z0-9]+$/', $hash ) ) {
            $embed = add_query_arg( 'h', $hash, $embed );
        }
    }
    if ( $embed ) {
        return '<iframe class="tutorial-embed" src="' . esc_url( $embed ) . '" title="' . esc_attr( $title ) . '" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen></iframe>';
    }
    $type = wp_check_filetype( $path );
    if ( ! empty( $type['type'] ) && 0 === strpos( $type['type'], 'video/' ) ) {
        return '<video controls playsinline preload="metadata" aria-label="' . esc_attr( $title ) . '" src="' . esc_url( $url ) . '">Seu navegador não suporta este vídeo.</video>';
    }
    return '<p><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">Abrir vídeo</a></p>';
}

function site_turoriais_tutorials_library() {
    if ( ! current_user_can( 'read' ) ) {
        wp_die( 'Você precisa entrar no painel para assistir aos tutoriais.' );
    }
    $tutorials = get_posts( array(
        'post_type' => 'tutoriais', 'post_status' => 'publish', 'posts_per_page' => -1,
        'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
    ) );
    ?>
    <div class="wrap flaneur-tutorials">
        <h1>Tutoriais</h1>
        <p>Aprenda a administrar o site. Escolha uma aula abaixo para assistir.</p>
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
            <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tutoriais' ) ); ?>">Adicionar tutorial</a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tutoriais' ) ); ?>">Gerenciar tutoriais</a></p>
        <?php endif; ?>
        <?php site_turoriais_render_playlist(); ?>
        <?php if ( ! $tutorials && ! get_option( 'site_turoriais_playlist_url', '' ) ) : ?>
            <div class="tutorial-empty"><h2>As aulas aparecerão aqui</h2><p>Nenhum tutorial foi publicado ainda.</p></div>
        <?php else : ?>
            <div class="tutorial-grid">
            <?php foreach ( $tutorials as $tutorial ) : ?>
                <article class="tutorial-card">
                    <h2><?php echo esc_html( $tutorial->post_title ); ?></h2>
                    <?php $video = get_post_meta( $tutorial->ID, '_flaneur_tutorial_video_url', true ); ?>
                    <?php if ( $video ) : ?>
                        <div class="tutorial-player"><?php echo site_turoriais_tutorial_player( $video, $tutorial->post_title ); ?></div>
                    <?php else : ?>
                        <p>Vídeo ainda não disponível.</p>
                    <?php endif; ?>
                    <div class="tutorial-description"><?php echo wp_kses_post( wpautop( $tutorial->post_content ) ); ?></div>
                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                        <a class="button" href="<?php echo esc_url( get_edit_post_link( $tutorial->ID, 'raw' ) ); ?>">Editar tutorial</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
