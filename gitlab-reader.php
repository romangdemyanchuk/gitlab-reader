<?php

/*
    Plugin Name: Gitlab reader
    Description: Plugin for getting information from gitlab
    Author: Roman Demianchuk
*/

// Admin page
function my_admin_menu() {
    add_menu_page(
        __( 'Gitlab page', 'my-textdomain' ),
        __( 'Gitlab menu', 'my-textdomain' ),
        'manage_options',
        'gitlab-page',
        'my_admin_page_contents',
        'dashicons-schedule',
        3
    );
}
add_action( 'admin_menu', 'my_admin_menu' );

function myadmin_page_contents() {
    ?>
    <h1> <?php esc_html_e( 'Gitlab page with settings', 'my-plugin-textdomain' ); ?> </h1>
    <form method="POST" action="index.php">
        <?php
        settings_fields( 'gitlab-page' );
        do_settings_sections( 'gitlab-page' );
        submit_button();
        ?>
    </form>
    <?php
}

add_action( 'admin_init', 'my_settings_init' );

function my_settings_init() {
    add_settings_section(
        'gitlab_page_setting_section',
        __( 'Custom settings', 'my-textdomain' ),
        '',
        'gitlab-page'
    );

    add_settings_field(
        'gitlab_options',
        __( 'My custom setting field', 'my-textdomain' ),
        'my_setting_markup',
        'gitlab-page',
        'gitlab_page_setting_section'
    );

    register_setting( 'gitlab-page', 'gitlab_options' );
}

function my_setting_markup() {
    ?>
    <label for="my-input"><?php _e( 'Gitlab key' ); ?></label>
    <input type="text" id="gitlab_options" name="gitlab_options" value="<?php echo get_option( 'gitlab_options' ); ?>">
    <?php
}


function get_curl_resource() {
    static $ch = null;
    if ( is_null( $ch ) ) {
        $ch = curl_init();
        $headers = [
            'Private-Token: ' . 'u1tzeW8Yh-ZUsnLzQhg8'
        ];
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    }
    return $ch;
}

function get_gitlab_response( string $url_affix, bool $use_as_affix = true ) {
    $ch = get_curl_resource();
    curl_setopt( $ch, CURLOPT_URL, $use_as_affix ? 'https://gitlab.com/api/v4/'. $url_affix : $url_affix );

    $res = curl_exec( $ch );
    if (  $res ) {
        return $res;
    }
    $res_decoded = json_decode( $res, true );

    if ( json_last_error() === JSON_ERROR_NONE ) {
        return $res_decoded;
    }
    return $res;
}

//484210
function mm_projects($atts)
{
    $atts = shortcode_atts(['project-id' => '484210'], $atts);
    $projects = get_gitlab_response("groups/{$atts['project-id']}/projects");

    $projects = json_decode($projects);

    if ($projects->message)
        return $projects->message;

    usort($projects, function ($p1, $p2) {
        return strtotime($p2->last_activity_at) <=> strtotime($p1->last_activity_at);
    });

    $result = '<ul>';
    foreach ($projects as $project) {
        $result .= '<li>' . esc_html($project->name) . '</li>';
    }
    $result .= '</ul>';
    return $result;
}

add_shortcode('mm_projects_shortcode', 'mm_projects');


//27137347
function mm_commits($atts){
    $atts = shortcode_atts(['project-id' => '27137347'], $atts);
    $commits = get_gitlab_response( "projects/{$atts['project-id']}/repository/commits" );
    $commits = json_decode($commits);
    $result = '<ul>';
    foreach($commits as $key => $value) {
        $result .= '<li>' . esc_html( $commits[$key]->title ) . '</li>';
    }
    $result .= '</ul>';
    return $result;
}

add_shortcode('mm_commits_shortcode', 'mm_commits');

