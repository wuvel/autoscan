<?php
require_once 'PackagePortal.php';
error_reporting(0);
$i = 0; $count_wallet = 0;
$handle = fopen("accounts.txt", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $i++;
        $empas = explode("|", $line);
        $empas = str_replace(PHP_EOL, "", $empas);
        $email = $empas[0];
        $password = $empas[1];

        echo "[$i] Logging in.. ";
        $login = PackagePortal::Login($email, $password);
        $result_login = json_decode($login);
        $bearer = $result_login->idToken;

        if ($bearer != "") {

            // Update Last Login
            $update_last_login = PackagePortal::UpdateLastLogin($bearer);
            $update_last_login = json_decode($update_last_login);
            $user_id = $update_last_login->data->update_users->returning[0]->id;

            echo "[OK] | [$email] [$user_id]" . PHP_EOL;
            
            // Get Wallet
            $get_wallet = PackagePortal::GetWallet($bearer);
            $result = json_decode($get_wallet);
            $wallet_address = $result->data->users[0]->wallet_address;
            if ($wallet_address != "") {
                echo "   Wallet Address: " . $wallet_address . PHP_EOL;


            // Get All Scans
            $get_scan = PackagePortal::GetAllScan($bearer);
            $get_scan = json_decode($get_scan);

            if (count($get_scan->data->scans) > 0) {
                echo '   Total Scan: ' . count($get_scan->data->scans) . ' | Last Scan: ' . date( "Y-m-d H:i:s", strtotime( $get_scan->data->scans[0]->created_at) ) . PHP_EOL;
            }else{
                echo '   Total Scan: 0' . PHP_EOL;
            }

            PackagePortal::InsertNewInstall($bearer, $user_id);

            echo '   Generate Tracking Numbers... ' . PHP_EOL;
            $rand = mt_rand(1000000, 9999999);
            $resi = "S0000018" . $rand;
            $generate_resi = PackagePortal::ValidateResi($resi);
            if (isset($generate_resi['tracking_numbers']) && count($generate_resi['tracking_numbers']) > 0) {
                foreach ($generate_resi['tracking_numbers'] as $key => $tracking_number) {
                    $scan_id = "";
                    echo "   " . $tracking_number . "\t[Origin: " . $generate_resi['origin'] . " | Destination: " . $generate_resi['destination'] . "]  => Scanning...";
                    $scan = PackagePortal::DoScan($bearer, $user_id, $tracking_number);
                    $scan = json_decode($scan);
                    $scan_id = $scan->data->insert_scans->returning[0]->id;
                    if ($scan_id != "") {
                        $review = PackagePortal::DoReview($bearer, $scan_id);
                        $review = json_decode($review);
                        if ($review->data->insert_ratings->affected_rows) {
                            echo ' [OK]' . PHP_EOL;
                        }else{
                            echo ' [OK] Error: Review Failed..' . PHP_EOL;
                        }
                    }else{
                        print_r($scan);
                    }
                }
            }
            echo PHP_EOL;
            }else{
                echo "   Wallet is empty!" . PHP_EOL;
            }

        }else{
            echo "[Error]" . PHP_EOL;
        }
    }

    fclose($handle);
} else {
    echo 'Error read file' . PHP_EOL;
} 