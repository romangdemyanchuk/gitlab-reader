<?php
/**
 * Plugin Name: Gitlab reader
 * Description: Plugin for getting information from gitlab
 * Author: Roman Demianchuk
 * Text Domain: gitlab-reader
 * Domain Path: /languages
*/

add_action( 'init', 'mm_gitlr_load_textdomain' );

function mm_gitlr_load_textdomain() {
	load_plugin_textdomain( 'gitlab-reader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function mm_gitlr_admin_page_contents() {
	add_menu_page(
		__( 'Gitlab page', 'gitlab-reader' ),
		__( 'Gitlab menu', 'gitlab-reader' ),
		'manage_options',
		'gitlab-page',
		'mm_gitlr_admin_page',
		'dashicons-schedule',
		3
	);
}
add_action( 'admin_menu', 'mm_gitlr_admin_page_contents' );

function mm_gitlr_admin_page() {
	?>
    <h1> <?php esc_html_e( 'Welcome to my custom gitlab page.', 'gitlab-reader' ); ?> </h1>
    <form method="POST" action="options.php">
		<?php
		settings_fields( 'gitlab-page' );
		do_settings_sections( 'gitlab-page' );
		submit_button();
		?>
    </form>
	<?php
}
add_action( 'admin_init', 'mm_gitlr_settings_init' );

function mm_gitlr_settings_init() {
	add_settings_section(
		'gitlab_page_setting_section',
		__( 'Custom settings', 'gitlab-reader' ),
		'mm_gitlr_setting_section_callback_function',
		'gitlab-page'
	);

	add_settings_field(
		'my_setting_field',
		__( 'My custom setting field', 'gitlab-reader' ),
		'mm_gitlr_setting_markup',
		'gitlab-page',
		'gitlab_page_setting_section'
	);

	register_setting( 'gitlab-page', 'my_setting_field' );
}

function mm_gitlr_setting_markup() {
	?>
    <label for="my-gitlab_option"><?php _e( 'My Input' ); ?></label>
    <input type="text" id="gitlab_option" name="gitlab_option" value="<?php echo get_option( 'gitlab_option' ); ?>">
	<?php
}

function mm_gitlr_get_curl_resource() {
    static $ch = null;
    if (is_null($ch)) {
        $ch = curl_init();
        $headers = [
            'Private-Token: ' . 'u1tzeW8Yh-ZUsnLzQhg8'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }
    return $ch;
}

function mm_gitlr_get_gitlab_response(string $url_affix, bool $use_as_affix = true) {
    $ch = mm_gitlr_get_curl_resource();
    curl_setopt($ch, CURLOPT_URL, $use_as_affix ? 'https://gitlab.com/api/v4/' . $url_affix : $url_affix);

    $res = curl_exec($ch);
    if ($res) {
        return $res;
    }
    $res_decoded = json_decode($res, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return $res_decoded;
    }
    return $res;
}

//484210
function mm_gitlr_projects($atts) {
    $atts = shortcode_atts(['project-id' => '484210'], $atts);
    $projects = mm_gitlr_get_gitlab_response("groups/{$atts['project-id']}/projects");

    $projects = json_decode($projects);

    if ($projects->message)
        return $projects->message;

    usort($projects, function ($p1, $p2) {
        return strtotime($p2->last_activity_at) <=> strtotime($p1->last_activity_at);
    });
    ob_start();
    ?>
    <ul>
        <?php foreach ($projects as $project) : ?>
            <li> <?php echo esc_html($project->name) ?> </li>
        <?php
        endforeach;
        ?>
    </ul>
    <?php
    return ob_get_clean();
}

add_shortcode('mm_gitlr_projects_shortcode', 'mm_gitlr_projects');

//27137347
function mm_gitlr_commits($atts) {
    $atts = shortcode_atts(['project-id' => '27137347'], $atts);
    $commits = mm_gitlr_get_gitlab_response("projects/{$atts['project-id']}/repository/commits");
    $commits = json_decode($commits);
    ob_start();
    ?>
    <ul>
        <?php foreach ($commits as $value) : ?>
            <li> <?php echo esc_html($value->title) ?> </li>
        <?php
        endforeach;
        ?>
    </ul>
    <?php
    return ob_get_clean();
}
add_shortcode('mm_gitlr_commits_shortcode', 'mm_gitlr_commits');

