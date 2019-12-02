<?php
$config = include(dirname(__FILE__) . '/config.php');
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8"/>
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">
    <title>WordCamp Taipei 2019 簽到工具</title>
</head>
<body>

<?php

function alert($str)
{
    ?>
    <script type="text/javascript">
      alert('<?php echo $str; ?>');
      location.href = '/announcement/';
    </script>
    <?php
}

function parsing_hdata($filename)
{
    $filename = dirname(__FILE__) . "/{$filename}";

    $csv  = array_map("str_getcsv", file($filename, FILE_SKIP_EMPTY_LINES));
    $keys = array_shift($csv);

    foreach ($csv as $i => $row) {
        $csv[$i] = array_combine($keys, $row);
    }

    return $csv;
}

$data = parsing_hdata($config['file']);

$action           = $_POST['action'];

switch ($action) {
    case 'search':
        $resp = 0;
        $name     = $_POST['name'];
        $email    = $_POST['email'];
        $attended = 0;

        foreach ($data as $key => $value) {
            $fullsearch = /*$value['Ticket Buyer Name'] .*/
                $value['First Name'] . $value['Last Name'];

            if (strpos(strtolower($fullsearch), strtolower($name)) !== false && strtolower($value['E-mail Address']) == strtolower($email)) {
                $resp = $value;
            }
        }

        //檢查是否已簽到
        if ($resp !== 0 && file_exists('log/' . $resp['Attendee ID'] . "-" . $email . ".log")) {
            $attended = 1;
        }

        if ($resp === 0) {
            echo "<script>
		alert('查無此人，請輸入票券註冊時正確的名稱與信箱。 / No such attendee. Please try to remenber your first name and Email in the ticket.');
		</script>";
            ?>

            <form method="POST" action="" class="registered_form">
                <h1>WordCamp Taipei 2019 簽到工具 / Check-in tool</h1>
                <div><span>票券上的姓？ / What's your last name?</span></div>
                <input class="input_style" type="text" name="name" value="" placeholder="Last Name"/>
                <div><span>註冊票券的信箱？ / Which Email you registered the ticket?</span></div>
                <input class="input_style" type="text" name="email" value="" placeholder="Email"/>
                <input type="hidden" name="action" value="search"/>
                <input class="submit_btn" type="submit" name="submit" value="送出"/>
            </form>

            <?php
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
                <script type="text/javascript">alert('請把此畫面顯示給工作人員確認報到。/ Please show this screen to staff for check-in.');</script>
                <div class="Sing_in_content">
                    <?php echo $attended == 1 ? "<div><span style='color:red;'>此人已完成報到！</span></div>" : "" ?>
                    <div><span>購票人：</span><?php echo $buyer; ?></div>
                    <div><span>持有人：</span><?php echo $holder; ?></div>
                    <div><span>票種：</span><?php echo $Ticket_Type; ?></div>
                    <div><span>飲食偏好：</span><?php echo $Food_Preference; ?></div>
                    <div><span>衣服尺寸：</span><?php echo $T_Shirt_Size; ?></div>
                    <div><span>危及生命的過敏症：</span><?php echo $Life_threatening_allergy; ?></div>
                    <div><span>身心障礙輔具需求：</span><?php echo $Accessibility_needs; ?></div>
                    <form action="" method="POST" id="checkin">
                        <input type="hidden" name="email" value="<?php echo $resp["E-mail Address"]; ?>">
                        <input type="hidden" name="name" value="<?php echo $holder; ?>">
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

                <button class="checkin_go" onclick="checkin_confirm();">簽到 / Checkin</button>
            </div>

            <?php
        }
        break;
    case 'update':
        //檢查是否已簽到
        if (!$config['url']) {
            alert("無法簽到，請與系統管理員聯繫！");
        }

        if (file_exists('log/' . $_POST['camptix_id'] . "-" . $_POST['email'] . ".log")) {
            alert("注意！" . $_POST['camptix_id'] . " " . $_POST['name'] . " 已重複簽到！");
        } else {
            alert($_POST['camptix_id'] . " " . $_POST['name'] . " 已完成簽到！");
        }

        $url = $config['url'] . '&camptix_id=' . $_POST['camptix_id'];

        $ch  = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");

        $output = curl_exec($ch);

        curl_close($ch);

        $r = json_decode($output, true);

        if ($r['success']) {
            if (!is_dir('log'))
            {
                mkdir('log');
            }

            file_put_contents('log/' . $_POST['camptix_id'] . "-" . $_POST['email'] . ".log", $output);
        }

        break;
    default:
        ?>
        <form method="POST" action="" class="registered_form">
            <h1>WordCamp Taipei 2019 簽到工具 / Check-in tool</h1>
            <div><span>票券上的姓？ / What's your last name?</span></div>
            <input class="input_style" type="text" name="name" value="" placeholder="Last Name"/>
            <div><span>註冊票券的信箱？ / Which Email you registered the ticket?</span></div>
            <input class="input_style" type="text" name="email" value="" placeholder="Email"/>
            <input type="hidden" name="action" value="search"/>
            <input class="submit_btn" type="submit" name="submit" value="送出 / Submit"/>
        </form>
        <?php
        break;
}

?>

<style>
    body {
        margin: 0;
        background: #fdf6d4;
    }

    h1 {
        color: #259db4;
    }

    .registered_form {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        min-width: 320px;
    }

    .registered_form input {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }

    .registered_form .input_style {
        border: 1px solid #259db4;
        padding: 15px;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .submit_btn {
        border: 0;
        background-color: #259db4;
        color: #fff;
        font-size: 18px;
        padding: 15px;
        transition: all 0.5s;
        cursor: pointer;
    }

    .submit_btn:hover {
        opacity: 0.8;
    }

    .Sign_in {
        max-width: 500px;
        margin: 0 auto;
        background: #259db4;

        color: #fff;
    }

    .Sign_in .Sing_in_content div {
        margin-bottom: 15px;
    }

    .Sing_in_content {
        padding: 15px;
    }

    .checkin_go {
        background: #003f4c;
        border: 0;
        width: 100%;
        padding: 15px;
        font-size: 18px;

        color: #fff;
        transition: all 0.5s;
        cursor: pointer;
    }

    .checkin_go:hover {
        opacity: 0.8;
    }
</style>
</body>
</html>
