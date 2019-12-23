<?php
/**
 * Plugin Name:       WCTPE Check-in
 * Plugin URI:        https://wctpe.tw/
 * Description:       WordCamp Taipei Check-in Tool
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.3
 * Author:            Bruce Lee, Yuli Yang
 * Author URI:        https://2019.taipei.wordcamp.org/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wctpe-checkin
 * Domain Path:       /languages
 */

date_default_timezone_set(get_option('timezone_string'));

add_action('admin_menu', 'wctpe_checkin_setup_menu');

function wctpe_checkin_setup_menu()
{
    add_menu_page('WCTPE Check-in Tool', 'WCTPE Check-in', 'manage_options', 'wctpe_checkin', 'wctpe_checkin_init');
}

function wctpe_checkin_init()
{
    wctpe_checkin_handle_post();
    ?>
    <h1>WCTPE Check-in Tool</h1>
    <h2>Upload Attendee Data</h2>
    <!-- Form to handle the upload - The enctype value here is very important -->
    <p>Please visit your official WordCamp site.<br>Go to Tickets ➞ Tools ➞ Export ➞ Export all attendees data to <strong>CSV</strong> file. </p>
    <form method="post" enctype="multipart/form-data">
        <input type='file' id='wctpe_checkin_upload_pdf' name='wctpe_checkin_upload_pdf' required/>
        <?php submit_button('Upload') ?>
    </form>
    <form method="post" action="options.php">
        <?php settings_fields('wctpe_checkin_options_group'); ?>
        <h3>Settings</h3>
        <table>
            <tr valign="top">
                <th scope="row"><label for="url">WordCamp Secret Link</label></th>
                <td><input type="url" id="url" name="url" value="<?php echo get_option('url'); ?>" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="file">Attendee Data File Name</label></th>
                <td><input type="text" id="file" name="file" value="<?php echo get_option('file'); ?>" required/><p>File name not including full URL. (ex. camptix-export-2020-01-01.csv)</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="checkin_at">Check-in Starting at</label></th>
                <td><input type="text" id="checkin_at" name="checkin_at" value="<?php echo get_option('checkin_at'); ?>" required/><p>Format：Y-m-d H:i (ex. 2020-01-01 09:00)</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="redirect">Success Check-in Redirect URL</label></th>
                <td><input type="url" id="redirect" name="redirect" value="<?php echo get_option('redirect'); ?>" required/></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php
}

function wctpe_checkin_register_settings()
{
    add_option('url', '');
    add_option('file', '');
    add_option('checkin_at', '');
    add_option('redirect', '');
    register_setting('wctpe_checkin_options_group', 'url', 'wctpe_checkin_callback');
    register_setting('wctpe_checkin_options_group', 'file', 'wctpe_checkin_callback');
    register_setting('wctpe_checkin_options_group', 'checkin_at', 'wctpe_checkin_callback');
    register_setting('wctpe_checkin_options_group', 'redirect', 'wctpe_checkin_callback');
}

add_action('admin_init', 'wctpe_checkin_register_settings');

function wctpe_checkin_handle_post()
{
    // First check if the file appears on the _FILES array
    if (isset($_FILES['wctpe_checkin_upload_pdf'])) {
        $pdf = $_FILES['wctpe_checkin_upload_pdf'];

        // Use the wordpress function to upload
        // wctpe_checkin_upload_pdf corresponds to the position in the $_FILES array
        // 0 means the content is not associated with any other posts
        $uploaded = media_handle_upload('wctpe_checkin_upload_pdf', 0);
        // Error checking using WP functions
        if (is_wp_error($uploaded)) {
            echo "<div class='notice notice-error is-dismissible'><p><strong>Error uploading file: " . $uploaded->get_error_message() . "</strong></p></div>";
        } else {
            echo "<div class='notice notice-success is-dismissible'><p><strong>File uploaded successfully!</strong></p></div>";
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
            <label for="username">購票姓氏 / Last Name</label>
            <input type="text" name="username" value="" placeholder="Last Name" required>
        </div>

        <div>
            <label for="email">購票信箱 / Email</label>
            <input type="email" name="email" value="" placeholder="Email" required>
        </div>

        <input type="hidden" name="action" value="search"/>
        <input type="submit" name="submit" value="Send"/>
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
		alert('查無此人，請輸入票券註冊時正確的姓氏與信箱。Cannot find your ticket information. Please make sure you enter the last name and email you used to purchase the ticket.');
		</script>";

                registration_form();
            } else {
                $holder                   = $resp['First Name'] . " " . $resp['Last Name'];
                $buyer                    = $resp['Ticket Buyer Name'];
                $Ticket_Type              = $resp["Ticket Type"];
                $Coupon                   = $resp["Coupon"];
                $Meal_Preference          = $resp["飲食偏好 / Meal Preference"];
                $T_Shirt_Size             = $resp["衣服大小 / T-Shirt Size"];
                $Country                  = $resp["國家 / Country"];
                $Language                 = $resp["溝通語言 / Language Spoken"];
                $After_Party              = $resp["你是否會參加會後交流派對？/ Will you attend the After Party?"];
                $Life_Threatening_Allergy = $resp["Life-threatening allergy"];
                $Accessibility_Needs      = $resp["Accessibility needs"];
                $wctpe_checkin_id         = $resp["Attendee ID"];
                ?>
                <div class="Sign_in">
                    <div class="Sing_in_content">
                        <?php echo $attended == 1 ? "<div><span style='color:red;'>此人已完成報到！</span></div>" : "" ?>
                        <p>請將此畫面交給工作人員進行簽到。Please show this to the staff for check-in.</p>
                        <div><span>持有人 / Ticket Owner</span><?php echo $holder; ?></div>
                        <div><span>購票人 / Ticket Purchased By</span><?php echo $buyer; ?></div>
                        <div><span>票種 / Ticket Type</span><?php echo $Ticket_Type; ?></div>
                        <?php if( $Coupon ) : ?>
                            <div><span>優惠券 / Coupon</span><?php echo $Coupon; ?></div>
                        <?php endif; ?>
                        <div><span>溝通語言 / Language Spoken</span><?php echo $Language; ?></div>
                        <div><span>國家 / Country</span><?php echo $Country; ?></div>
                        <div><span>危及生命的過敏症 / Life-threatening Allergy</span><?php echo $Life_Threatening_Allergy; ?></div>
                        <div><span>身心障礙輔助需求 / Accessibility Needs </span><?php echo $Accessibility_Needs; ?></div>
                        <div><span>飲食偏好 / Meal Preference</span><?php echo $Meal_Preference; ?></div>
                        <?php if( $T_Shirt_Size ) : ?>
                            <div><span>衣服尺寸 / T-shirt Size</span><?php echo $T_Shirt_Size; ?></div>
                        <?php endif; ?>
                        <div><span>交流派對 / After Party</span><?php echo $After_Party; ?></div>
                        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" id="checkin">
                            <input type="hidden" name="email" value="<?php echo $resp["E-mail Address"]; ?>">
                            <input type="hidden" name="username" value="<?php echo $holder; ?>">
                            <input type="hidden" name="wctpe_checkin_id" value="<?php echo $wctpe_checkin_id; ?>">
                            <input type="hidden" name="action" value="update"/>
                        </form>
                        <script type="text/javascript">
                          function checkin_confirm() {
                            if (confirm("你是工作人員嗎？不是請勿自行簽到，以免影響報到權益。Are you a staff? Do not check-in on your own. Double check-in is not allowed.")) {
                              document.getElementById("checkin").submit();
                            }
                          }
                        </script>
                    </div>

                    <?php
                    if (date('Y-m-d H:i') >= get_option('checkin_at')): ?>
                        <button class="checkin_go" onclick="checkin_confirm();">簽到 / Check-in</button>
                    <?php else: ?>
                        <p>開放簽到時間 / Check-in Opens at<br><?php echo get_option('checkin_at'); ?></p>
                    <?php endif; ?>

                </div>

                <?php
            }
            break;
        case 'update':
            //檢查是否已簽到
            if (!get_option('url')) {
                alert("無法簽到，請與系統管理員聯繫！Cannot check-in. Please contact system admin.");
            }

            if (file_exists(__DIR__ . '/log/' . $_POST['wctpe_checkin_id'] . "-" . $_POST['email'] . ".log")) {
                alert("注意！已重複簽到。Warning! Double check-in. [" . $_POST['wctpe_checkin_id'] . " " . $_POST['username'] . "]");
            } else {
                alert("[" . $_POST['wctpe_checkin_id'] . " " . $_POST['username'] . "] 已完成簽到！Check-in Complete.");
            }

            $url = get_option('url') . '&wctpe_checkin_id=' . $_POST['wctpe_checkin_id'];

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

                file_put_contents(__DIR__ . '/log/' . $_POST['wctpe_checkin_id'] . "-" . $_POST['email'] . ".log", $output);
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
function wctpe_checkin_shortcode()
{
    wp_enqueue_style( 'wctpe-checkin', plugin_dir_url( __FILE__ ) . 'wctpe-checkin.css' );
    ob_start();
    custom_registration_function();

    return ob_get_clean();
}

//Adding wctpe_checkin shortcode

add_shortcode('wctpe_checkin-form', 'wctpe_checkin_shortcode');
