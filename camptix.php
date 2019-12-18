<?php
/**
 * Plugin Name:       camptix
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Handle the basics with this plugin.
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.3
 * Author:            Bruce Lee
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       camptix
 * Domain Path:       /languages
 */

add_action('admin_menu', 'camptix_setup_menu');

function camptix_setup_menu()
{
    add_menu_page('Camptix Page', 'Camptix', 'manage_options', 'camptix', 'camptix_init');
}

function camptix_init()
{
    camptix_handle_post();
    ?>
    <h1>Hello World!</h1>
    <h2>Upload a File</h2>
    <!-- Form to handle the upload - The enctype value here is very important -->
    <form method="post" enctype="multipart/form-data">
        <input type='file' id='camptix_upload_pdf' name='camptix_upload_pdf'/>
        <?php submit_button('Upload') ?>
    </form>
    <form method="post" action="options.php">
        <?php settings_fields('myplugin_options_group'); ?>
        <h3>設定</h3>
        <table>
            <tr valign="top">
                <th scope="row"><label for="url">Url</label></th>
                <td><input type="text" id="url" name="url" value="<?php echo get_option('url'); ?>"/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="file">File</label></th>
                <td><input type="text" id="file" name="file" value="<?php echo get_option('file'); ?>"/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="checkin_at">Checkin_at</label></th>
                <td><input type="text" id="checkin_at" name="checkin_at" value="<?php echo get_option('checkin_at'); ?>"/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="redirect">Redirect</label></th>
                <td><input type="text" id="redirect" name="redirect" value="<?php echo get_option('redirect'); ?>"/></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php
}

function myplugin_register_settings()
{
    add_option('url', '');
    add_option('file', '');
    add_option('checkin_at', '');
    add_option('redirect', '');
    register_setting('myplugin_options_group', 'url', 'myplugin_callback');
    register_setting('myplugin_options_group', 'file', 'myplugin_callback');
    register_setting('myplugin_options_group', 'checkin_at', 'myplugin_callback');
    register_setting('myplugin_options_group', 'redirect', 'myplugin_callback');
}

add_action('admin_init', 'myplugin_register_settings');

function myplugin_register_options_page()
{
    add_options_page('Page Title', 'Plugin Menu', 'manage_options', 'myplugin', 'myplugin_options_page');
}

add_action('admin_menu', 'myplugin_register_options_page');

function camptix_handle_post()
{
    // First check if the file appears on the _FILES array
    if (isset($_FILES['camptix_upload_pdf'])) {
        $pdf = $_FILES['camptix_upload_pdf'];

        // Use the wordpress function to upload
        // camptix_upload_pdf corresponds to the position in the $_FILES array
        // 0 means the content is not associated with any other posts
        $uploaded = media_handle_upload('camptix_upload_pdf', 0);
        // Error checking using WP functions
        if (is_wp_error($uploaded)) {
            echo "Error uploading file: " . $uploaded->get_error_message();
        } else {
            echo "File upload successful!";
        }
    }
}

function tuts_styl_incl()
{

    //wp_enqueue_style('tuts_styl_css_and_js', TUTS_REGISTRATION_INCLUDE_URL."front-style.css");
    //
    //wp_enqueue_script('tuts_styl_css_and_js');

}

add_action('wp_footer', 'tuts_styl_incl');

// function to login Shortcode

function registration_form()
{
    ?>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
        <div>
            <label for="username">票券上的姓？ / What\'s your last name?</label>
            <input type="text" name="username" value="" placeholder="Last Name">
        </div>

        <div>
            <label for="email">註冊票券的信箱？ / Which Email you registered the ticket?</label>
            <input type="email" name="email" value="" placeholder="Email">
        </div>

        <input type="hidden" name="action" value="search"/>
        <input type="submit" name="submit" value="送出"/>
    </form>
    <?php
}

function parsing_hdata($filename)
{
    $filename = wp_upload_dir()['path'] . "/{$filename}";

    $csv  = array_map("str_getcsv", file($filename, FILE_SKIP_EMPTY_LINES));
    $keys = array_shift($csv);

    foreach ($csv as $i => $row) {
        $csv[$i] = array_combine($keys, $row);
    }

    return $csv;
}

function alert($str)
{
    ?>
    <script type="text/javascript">
      alert('<?php echo $str; ?>');
      location.href = '<?php echo get_option('redirect'); ?>';
    </script>
    <?php
}

function complete_registration()
{
    global $username, $email, $action;

    $data             = parsing_hdata(get_option('file'));

    switch ($action) {
        case 'search':
            $resp = 0;
            $attended = 0;

            foreach ($data as $key => $value) {
                $fullsearch = /*$value['Ticket Buyer Name'] .*/
                    $value['First Name'] . $value['Last Name'];

                if (strpos(strtolower($fullsearch), strtolower($username)) !== false && strtolower($value['E-mail Address']) == strtolower($email)) {
                    $resp = $value;
                }
            }

            //檢查是否已簽到
            if ($resp !== 0 && file_exists(__DIR__ . '/log/' . $resp['Attendee ID'] . "-" . $email . ".log")) {
                $attended = 1;
            }

            if ($resp === 0) {
                echo "<script>
		alert('查無此人，請輸入票券註冊時正確的名稱與信箱。 / No such attendee. Please try to remenber your first name and Email in the ticket.');
		</script>";

                registration_form();
            } else {
                $holder                   = $resp['First Name'] . " " . $resp['Last Name'];
                $buyer                    = $resp['Ticket Buyer Name'];
                $Ticket_Type              = $resp["Ticket Type"];
                $Food_Preference          = $resp["飲食偏好 / Meal Preference"];
                $T_Shirt_Size             = $resp["衣服大小 / T-Shirt Size"];
                $Life_threatening_allergy = $resp["Life-threatening allergy"];
                $Accessibility_needs      = $resp["Accessibility needs"];
                $camptix_id               = $resp["Attendee ID"];
                ?>
                <div class="Sign_in">
                    <div class="Sing_in_content">
                        <?php echo $attended == 1 ? "<div><span style='color:red;'>此人已完成報到！</span></div>" : "" ?>
                        <div><span>購票人：</span><?php echo $buyer; ?></div>
                        <div><span>持有人：</span><?php echo $holder; ?></div>
                        <div><span>票種：</span><?php echo $Ticket_Type; ?></div>
                        <div><span>飲食偏好：</span><?php echo $Food_Preference; ?></div>
                        <div><span>衣服尺寸：</span><?php echo $T_Shirt_Size; ?></div>
                        <div><span>危及生命的過敏症：</span><?php echo $Life_threatening_allergy; ?></div>
                        <div><span>身心障礙輔具需求：</span><?php echo $Accessibility_needs; ?></div>
                        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" id="checkin">
                            <input type="hidden" name="email" value="<?php echo $resp["E-mail Address"]; ?>">
                            <input type="hidden" name="username" value="<?php echo $holder; ?>">
                            <input type="hidden" name="camptix_id" value="<?php echo $camptix_id; ?>">
                            <input type="hidden" name="action" value="update"/>
                        </form>
                        <script type="text/javascript">
                          function checkin_confirm() {
                            if (confirm("你是工作人員嗎？不是請勿自行簽到，以免影響報到權益。Are you staff? Don't do this without confirm from staff or you will not allow to check-in.")) {
                              document.getElementById("checkin").submit();
                            }
                          }
                        </script>
                    </div>

                    <?php
                    if (date('Y-m-d H:i') >= get_option('checkin_at')): ?>
                        <button class="checkin_go" onclick="checkin_confirm();">簽到 / Checkin</button>
                    <?php else: ?>
                        <button type="button" class="checkin_go" disabled>尚未開放簽到 / Can't Checkin yet</button>
                    <?php endif; ?>

                </div>

                <?php
            }
            break;
        case 'update':
            //檢查是否已簽到
            if (!get_option('url')) {
                alert("無法簽到，請與系統管理員聯繫！");
            }

            if (file_exists(__DIR__ . '/log/' . $_POST['camptix_id'] . "-" . $_POST['email'] . ".log")) {
                alert("注意！" . $_POST['camptix_id'] . " " . $_POST['username'] . " 已重複簽到！");
            } else {
                alert($_POST['camptix_id'] . " " . $_POST['username'] . " 已完成簽到！");
            }

            $url = get_option('url') . '&camptix_id=' . $_POST['camptix_id'];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");

            $output = curl_exec($ch);

            curl_close($ch);

            $r = json_decode($output, true);

            if ($r['success']) {
                if (!is_dir(__DIR__ . '/log')) {
                    mkdir(__DIR__ . '/log');
                }

                file_put_contents(__DIR__ . '/log/' . $_POST['camptix_id'] . "-" . $_POST['email'] . ".log", $output);
            }

            break;
        default:
            registration_form();
            break;
    }
}

function custom_registration_function()
{
    // sanitize user form input
    global $username, $email, $action;
    $username = sanitize_user($_POST['username']);
    $email    = sanitize_email($_POST['email']);
    $action   = sanitize_text_field($_POST['action']);

    // call @function complete_registration to create the user
    // only when no WP_error is found
    complete_registration();
}

// The callback function that will replace [book]
function camptix_shortcode()
{
    ob_start();
    custom_registration_function();

    return ob_get_clean();
}

//Adding camptix shortcode

add_shortcode('camptix-form', 'camptix_shortcode');
