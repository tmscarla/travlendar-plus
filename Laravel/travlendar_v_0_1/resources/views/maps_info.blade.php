<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <body>
        <?php
                $start = '45.478119,9.227189';
                $end = '45.484505,9.202805';
                $mode = 'transit'; /*driving, transit*/
                $departure_time = '1511578354';
                $key = 'AIzaSyBt2MXm0Ma4l2s4v2E0pvvFXmE_CR3FONo';
                $url = 'https://maps.googleapis.com/maps/api/directions/json?' . 'origin=' . $start
                    . '&destination=' . $end . '&mode=' . $mode . '&departure_time='. $departure_time . '&alternatives=true' . '&key=' . $key;
                // Get cURL resource
                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $url,
                ));
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                echo($resp);
                // Close request to clear up some resources
                curl_close($curl);
        ?>
    </body>
</html>