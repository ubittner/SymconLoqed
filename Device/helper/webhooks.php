<?php

/** @noinspection DuplicatedCode */
/** @noinspection HttpUrlsUsage */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait Webhooks
{
    #################### Public

    public function ListBridgeWebhooks(): string
    {
        $result = '[]';
        $response = $this->GetBridgeWebhooks();
        if (is_string($response) && is_array(json_decode($response, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            $responseData = json_decode($response, true);
            if (array_key_exists('httpCode', $responseData)) {
                $httpCode = $responseData['httpCode'];
                if ($httpCode == 200) {
                    if (array_key_exists('body', $responseData)) {
                        if (is_string($responseData['body']) && is_array(json_decode($responseData['body'], true)) && (json_last_error() == JSON_ERROR_NONE)) {
                            $result = $responseData['body'];
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function GetBridgeWebhooks(): string
    {
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        $deviceConfigKey = $this->ReadPropertyString('DeviceConfigKey');
        if ($bridgeIP == '' || $deviceConfigKey == '') {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return json_encode(['httpCode' => 0, 'body' => '']);
        }
        $endpoint = 'http://' . $bridgeIP . '/webhooks';
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $timeStamp = time();
        $hash = hash('sha256', pack('J', $timeStamp) . base64_decode($deviceConfigKey));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_URL           => $endpoint,
            CURLOPT_HEADER        => true,
            CURLOPT_HTTPHEADER    => [
                'HASH: ' . $hash,
                'TIMESTAMP: ' . $timeStamp,
                'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_FAILONERROR       => false,
            CURLOPT_CONNECTTIMEOUT_MS => $this->ReadPropertyInteger('Timeout'),
            CURLOPT_TIMEOUT           => 60]);
        $response = curl_exec($curl);
        $httpCode = 0;
        $body = '[]';
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $this->SendDebug(__FUNCTION__, 'Response body: ' . $body, 0);
            switch ($httpCode) {
                case 200:  # OK
                    $body = substr($response, $header_size);
                    break;
            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($curl);
        $result = ['httpCode' => $httpCode, 'body' => $body];
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        return json_encode($result);
    }

    public function CreateBridgeWebhook(string $URL): string
    {
        $this->SendDebug(__FUNCTION__, 'URL: ' . $URL, 0);
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        $deviceConfigKey = $this->ReadPropertyString('DeviceConfigKey');
        if ($bridgeIP == '' || $deviceConfigKey == '') {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return json_encode(['httpCode' => 0, 'body' => '']);
        }
        $endpoint = 'http://' . $bridgeIP . '/webhooks';
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $postdata['url'] = $URL;
        //Ensure post data is numeric (not = '0' but = 0)
        $postdata_bitmap['trigger_state_changed_open'] = 1;
        $postdata_bitmap['trigger_state_changed_latch'] = 1;
        $postdata_bitmap['trigger_state_changed_night_lock'] = 1;
        $postdata_bitmap['trigger_state_changed_unknown'] = 1;
        $postdata_bitmap['trigger_state_goto_open'] = 1;
        $postdata_bitmap['trigger_state_goto_latch'] = 1;
        $postdata_bitmap['trigger_state_goto_night_lock'] = 1;
        $postdata_bitmap['trigger_battery'] = 1;
        $postdata_bitmap['trigger_online_status'] = 1;
        //Not left to right, but right to left.
        $postdata_bitmap = array_reverse($postdata_bitmap);
        $bitmap = implode('', $postdata_bitmap);
        $bitmap = str_pad($bitmap, 32, '0', STR_PAD_LEFT);
        $bitmap = bindec($bitmap);
        $timeStamp = time();
        $hash = hash('sha256', $URL . pack('N', $bitmap) . pack('J', $timeStamp) . base64_decode($deviceConfigKey));
        $postdata = array_merge($postdata, $postdata_bitmap);
        $postdata = json_encode($postdata);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_URL           => $endpoint,
            CURLOPT_HEADER        => true,
            CURLOPT_HTTPHEADER    => [
                'HASH: ' . $hash,
                'TIMESTAMP: ' . $timeStamp,
                'Content-Type: application/json'],
            CURLOPT_POSTFIELDS        => $postdata,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_FAILONERROR       => false,
            CURLOPT_CONNECTTIMEOUT_MS => $this->ReadPropertyInteger('Timeout'),
            CURLOPT_TIMEOUT           => 60]);
        $response = curl_exec($curl);
        $httpCode = 0;
        $body = '[]';
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $this->SendDebug(__FUNCTION__, 'Response body: ' . $body, 0);
            switch ($httpCode) {
                case 200:  # OK
                    $body = substr($response, $header_size);
                    break;
            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($curl);
        $result = ['httpCode' => $httpCode, 'body' => $body];
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        return json_encode($result);
    }

    public function DeleteBridgeWebhook(int $WebhookID): string
    {
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        $deviceConfigKey = $this->ReadPropertyString('DeviceConfigKey');
        if ($bridgeIP == '' || $deviceConfigKey == '') {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return json_encode(['httpCode' => 0, 'body' => '']);
        }
        $endpoint = 'http://' . $bridgeIP . '/webhooks/' . $WebhookID;
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $timeStamp = time();
        $hash = hash('sha256', pack('J', $WebhookID) . pack('J', $timeStamp) . base64_decode($deviceConfigKey));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_URL           => $endpoint,
            CURLOPT_HEADER        => true,
            CURLOPT_HTTPHEADER    => [
                'HASH: ' . $hash,
                'TIMESTAMP: ' . $timeStamp,
                'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_FAILONERROR       => false,
            CURLOPT_CONNECTTIMEOUT_MS => $this->ReadPropertyInteger('Timeout'),
            CURLOPT_TIMEOUT           => 60]);
        $response = curl_exec($curl);
        $httpCode = 0;
        $body = '[]';
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $this->SendDebug(__FUNCTION__, 'Response body: ' . $body, 0);
            switch ($httpCode) {
                case 200:  # OK
                    $body = substr($response, $header_size);
                    break;
            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($curl);
        $result = ['httpCode' => $httpCode, 'body' => $body];
        $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($result), 0);
        return json_encode($result);
    }

    #################### Protected

    protected function ProcessHookData()
    {
        $timestamp = date('d.m.Y H:i:s');
        //Get incomming data from server
        $this->SendDebug(__FUNCTION__, 'Incoming data: ' . print_r($_SERVER, true), 0);
        //Get content
        $data = file_get_contents('php://input');
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        //Check credentials
        if (!array_key_exists('PHP_AUTH_USER', $_SERVER) || !array_key_exists('PHP_AUTH_PW', $_SERVER)) {
            $this->SendDebug(__FUNCTION__, 'Abort, no credentials found in the header!', 0);
            return;
        }
        $user = urldecode($_SERVER['PHP_AUTH_USER']);
        $password = urldecode($_SERVER['PHP_AUTH_PW']);
        $this->SendDebug(__FUNCTION__, 'User: ' . $user . ' Password: ' . $password, 0);
        $webHookUser = $this->ReadAttributeString('WebhookUser');
        $webHookPassword = $this->ReadAttributeString('WebhookPassword');
        if (($user != $webHookUser) || ($password != $webHookPassword)) {
            $this->SendDebug(__FUNCTION__, 'Abort, wrong user or password!', 0);
            return;
        }
        //Check hash
        if (array_key_exists('HTTP_HASH', $_SERVER) && array_key_exists('HTTP_TIMESTAMP', $_SERVER)) {
            $httpHash = urldecode($_SERVER['HTTP_HASH']);
            $this->SendDebug(__FUNCTION__, 'Incoming hash: ' . $httpHash, 0);
            $calculatedHash = hash('sha256', $data . pack('J', urldecode($_SERVER['HTTP_TIMESTAMP'])) . base64_decode($this->ReadPropertyString('DeviceConfigKey')));
            $this->SendDebug(__FUNCTION__, 'Calculated hash: ' . $calculatedHash, 0);
            if ($httpHash != $calculatedHash) {
                $this->SendDebug(__FUNCTION__, 'Abort, hash values are not the same!', 0);
                return;
            }
        }

        /*
            Examples:

            Action: NIGHT_LOCK
            {"go_to_state":"NIGHT_LOCK","event_type":"GO_TO_STATE_MANUAL_LOCK_REMOTE_NIGHT_LOCK","mac_wifi":"0123456789","mac_ble":"9876543210","key_local_id":0}
            {"requested_state":"NIGHT_LOCK","requested_state_numeric":3,"mac_wifi":"0123456789","mac_ble":"9876543210","event_type":"STATE_CHANGED_NIGHT_LOCK","key_local_id":0}

            Action: DAY_LOCK
            {"go_to_state":"DAY_LOCK","event_type":"GO_TO_STATE_MANUAL_LOCK_REMOTE_LATCH","mac_wifi":"0123456789","mac_ble":"9876543210","key_local_id":0}
            {"requested_state":"DAY_LOCK","requested_state_numeric":2,"mac_wifi":"0123456789","mac_ble":"9876543210","event_type":"STATE_CHANGED_LATCH","key_local_id":0}

            Action: OPEN
            {"go_to_state":"OPEN","event_type":"GO_TO_STATE_MANUAL_UNLOCK_REMOTE_OPEN","mac_wifi":"0123456789","mac_ble":"9876543210","key_local_id":0}
            {"requested_state":"OPEN","requested_state_numeric":1,"mac_wifi":"0123456789","mac_ble":"9876543210","event_type":"STATE_CHANGED_OPEN","key_local_id":0}
            {"requested_state":"DAY_LOCK","requested_state_numeric":2,"mac_wifi":"0123456789","mac_ble":"9876543210","event_type":"STATE_CHANGED_LATCH","key_local_id":255}
         */

        /*
            Reached state
            After the motor stopped running, the below JSON keys are sent for the triggers trigger_state_changed_open, trigger_state_changed_latch, trigger_state_changed_night_lock, trigger_state_changed_unknown:

            requested_state
            OPEN
            DAY_LOCK
            NIGHT_LOCK
            UNKNOWN

            event_type
            STATE_CHANGED_OPEN (requested_state = OPEN)
            STATE_CHANGED_LATCH (requested_state = DAY_LOCK)
            STATE_CHANGED_NIGHT_LOCK (requested_state = NIGHT_LOCK)
            STATE_CHANGED_UNKNOWN (requested_state = UNKNOWN)
            MOTOR_STALL (requested_state = UNKNOWN)
            STATE_CHANGED_OPEN_REMOTE (requested_state = OPEN)
            STATE_CHANGED_LATCH_REMOTE (requested_state = DAY_LOCK)
            STATE_CHANGED_NIGHT_LOCK_REMOTE (requested_state = NIGHT_LOCK)
            GO_TO_STATE_TOUCH_TO_LOCK (requested_state = NIGHT_LOCK)
         */

        /*
            Going to a state
            When the lock is going to a new position (it might not reach the position if the batteries are almost empty, for example) the below JSON keys are sent for the triggers  trigger_state_goto_open, trigger_state_goto_latch, trigger_state_goto_night_lock:

            go_to_state
            OPEN
            DAY_LOCK
            NIGHT_LOCK

            event_type
            GO_TO_STATE_INSTANTOPEN_OPEN (Touch to Open) (go_to_state = OPEN)
            GO_TO_STATE_INSTANTOPEN_LATCH (Auto Unlock) (go_to_state = DAY_LOCK)
            GO_TO_STATE_MANUAL_UNLOCK_BLE_OPEN (go_to_state = OPEN)
            GO_TO_STATE_MANUAL_LOCK_BLE_LATCH  (go_to_state = DAY_LOCK)
            GO_TO_STATE_MANUAL_LOCK_BLE_NIGHT_LOCK (go_to__state = NIGHT_LOCK)
            GO_TO_STATE_TWIST_ASSIST_OPEN (go_to_state = OPEN)
            GO_TO_STATE_TWIST_ASSIST_LATCH (go_to_state = DAY_LOCK)
            GO_TO_STATE_TWIST_ASSIST_LOCK (go_to_state = NIGHT_LOCK)
            GO_TO_STATE_MANUAL_UNLOCK_VIA_OUTSIDE_MODULE_PIN (go_to_state = OPEN)
            GO_TO_STATE_MANUAL_UNLOCK_VIA_OUTSIDE_MODULE_BUTTON (go_to_state = OPEN)
            GO_TO_STATE_TOUCH_TO_LOCK (go_to_state = NIGHT_LOCK)
            key_local_id (255 means unknown, e.g. manual opening by knob or pressing button)
         */

        /*
            Battery percentage
            When the lock sends the current battery percentage (every few hours) the below JSON keys are sent for the trigger trigger_battery:

            battery_type (0 = Alkaline, 1 = NiMH, 2 = Lithium (non-rechargable), 3 = unknown)
            battery_percentage

            {"battery_type":"UNKNOWN","battery_percentage":100,"mac_wifi":"0123456789","mac_ble":"9876543210"}
         */

        /*
            Online status
            When the lock loses the connection to the bridge, the below JSON keys are sent for the trigger trigger_online_status:

            wifi_strength (currently in dB, soon this will change to a percentage)
            ble_strength (currently in dB, soon this will change to a percentage. -1 means the lock is not connected)

            {"wifi_strength":-24,"ble_strength":-59,"mac_wifi":"0123456789","mac_ble":"9876543210"}
         */

        if (is_string($data) && is_array(json_decode($data, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            $deviceData = json_decode($data, true);
            //Requested state
            if (array_key_exists('requested_state', $deviceData)) {
                $state = $deviceData['requested_state'];
                switch ($state) {
                    case 'NIGHT_LOCK':
                        $smartLockValue = 0;
                        $deviceState = 3;
                        $action = $this->Translate('Locked');
                        break;

                    case 'DAY_LOCK':
                        $smartLockValue = 1;
                        $deviceState = 2;
                        $action = $this->Translate('Unlocked');
                        break;

                    case 'OPEN':
                        $smartLockValue = 2;
                        $deviceState = 1;
                        $action = $this->Translate('Opened');
                        break;
                }
                //Set values
                if (isset($smartLockValue)) {
                    $this->SetValue('SmartLock', $smartLockValue);
                }
                if (isset($deviceState)) {
                    $this->SetValue('DeviceState', $deviceState);
                }
                //Update log
                if (array_key_exists('key_local_id', $deviceData)) {
                    $key = (string) $deviceData['key_local_id'];
                }
                if (isset($action) && isset($key)) {
                    $this->UpdateActivityLog($timestamp, $action, $key);
                }
            }
            //Battery
            if (array_key_exists('battery_percentage', $deviceData)) {
                $this->SetValue('BatteryCharge', $deviceData['battery_percentage']);
            }
            if (array_key_exists('battery_type', $deviceData)) {
                $batteryType = $deviceData['battery_type'];
                if ($batteryType != 'UNKNOWN' && is_int($batteryType)) {
                    $this->SetValue('BatteryType', $batteryType);
                }
            }
            //Online state
            if (array_key_exists('wifi_strength', $deviceData) && array_key_exists('ble_strength', $deviceData)) {
                $onlineState = 1;
                if ($deviceData['wifi_strength'] < 0) {
                    $onlineState = 0;
                }
                if ($deviceData['ble_strength'] < 0) {
                    $onlineState = 0;
                }
                $this->SetValue('OnlineState', $onlineState);
            }
        }
    }

    #################### Private

    private function RegisterWebHook($WebHook): void
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBHOOK_GUID);
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'WebHook was successfully registered.', 0);
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    private function UnregisterWebHook($WebHook): void
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_WEBHOOK_GUID);
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            $index = null;
            foreach ($hooks as $key => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        $found = true;
                        $index = $key;
                        break;
                    }
                }
            }
            if ($found === true && !is_null($index)) {
                array_splice($hooks, $index, 1);
                IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
                $this->SendDebug(__FUNCTION__, 'WebHook was successfully unregistered.', 0);
            }
        }
    }

    private function GenerateWebhookCredentials(): void
    {
        if ($this->ReadAttributeString('WebhookUser') != '' && $this->ReadAttributeString('WebhookPassword') != '') {
            $this->SendDebug(__FUNCTION__, 'Abort, we already have a user and password for the webhook!', 0);
            return;
        }
        $length = 12;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomUser = '';
        for ($i = 0; $i < $length; $i++) {
            $randomUser .= $characters[rand(0, $charactersLength - 1)];
        }
        $this->WriteAttributeString('WebhookUser', $randomUser);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        $this->WriteAttributeString('WebhookPassword', $randomPassword);
    }

    private function ManageBridgeWebhooks(): void
    {
        $hostIP = $this->ReadPropertyString('HostIP');
        if ($hostIP == '') {
            $this->SendDebug(__FUNCTION__, 'Abort, host ip is missing!', 0);
            return;
        }
        $webhookURL = 'http://' . $this->ReadAttributeString('WebhookUser') . ':' . $this->ReadAttributeString('WebhookPassword') . '@' . $hostIP . ':' . $this->ReadPropertyInteger('HostPort') . '/hook/loqed/device/' . $this->InstanceID;
        $response = $this->GetBridgeWebhooks();
        if (is_string($response) && is_array(json_decode($response, true)) && (json_last_error() == JSON_ERROR_NONE)) {
            $responseData = json_decode($response, true);
            if (array_key_exists('httpCode', $responseData)) {
                $httpCode = $responseData['httpCode'];
                if ($httpCode == 200) {
                    if (array_key_exists('body', $responseData)) {
                        if (is_string($responseData['body']) && is_array(json_decode($responseData['body'], true)) && (json_last_error() == JSON_ERROR_NONE)) {
                            $this->SendDebug(__FUNCTION__, 'Actual data: ' . $responseData['body'], 0);
                            $actualData = json_decode($responseData['body'], true);
                            $useWebhook = $this->ReadPropertyBoolean('UseWebhook');
                            if (!empty($actualData)) {
                                $existing = false;
                                foreach ($actualData as $data) {
                                    if (array_key_exists('url', $data)) {
                                        if ($data['url'] == $webhookURL) {
                                            $existing = true;
                                        }
                                    }
                                }
                                if ($useWebhook && !$existing) {
                                    //Register
                                    if ($webhookURL != '') {
                                        $this->CreateBridgeWebhook($webhookURL);
                                    }
                                }
                                if (!$useWebhook && $existing) {
                                    //Unregister
                                    foreach ($actualData as $data) {
                                        if (array_key_exists('url', $data)) {
                                            if ($data['url'] == $webhookURL) {
                                                if (array_key_exists('id', $data)) {
                                                    //Unregister
                                                    $this->DeleteBridgeWebhook($data['id']);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if (empty($actualData) && $useWebhook) {
                                //Register
                                if ($webhookURL != '') {
                                    $this->CreateBridgeWebhook($webhookURL);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}