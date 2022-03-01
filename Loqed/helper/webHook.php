<?php

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait Helper_webHook
{
    #################### Protected

    protected function ProcessHookData()
    {
        //Get incomming data from server
        $this->SendDebug(__FUNCTION__, 'Incoming data: ' . print_r($_SERVER, true), 0);
        //Get content
        $data = file_get_contents('php://input');
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data, 0);
        // Check credentials
        $user = urldecode($_SERVER['PHP_AUTH_USER']);
        $password = urldecode($_SERVER['PHP_AUTH_PW']);
        $this->SendDebug(__FUNCTION__, 'User: ' . $user . ' Password: ' . $password, 0);
        $webHookUser = $this->ReadAttributeString('WebHookUser');
        $webHookPassword = $this->ReadAttributeString('WebHookPassword');
        if (($user != $webHookUser) || ($password != $webHookPassword)) {
            $this->SendDebug(__FUNCTION__, 'Abort, wrong user or password!', 0);
            return;
        }
        $this->UpdateDeviceState($data);
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

    private function PrepareWebHook(): void
    {
        if (!empty($this->ReadAttributeString('WebHookURL'))) {
            return;
        }
        // Get ipmagic address and add webhook credentials
        $ids = IPS_GetInstanceListByModuleID(self::CORE_CONNECT_GUID);
        if (count($ids) > 0) {
            $url = CC_GetURL($ids[0]);
            $credentials = substr($url, 8);
            $credentials = substr($credentials, 0, -11);
            $credentials = str_shuffle($credentials);
            $user = substr($credentials, 0, 8);
            $this->WriteAttributeString('WebHookUser', $user);
            $password = substr($credentials, -8);
            $this->WriteAttributeString('WebHookPassword', $password);
            $credentials = urlencode($user) . ':' . urlencode($password) . '@';
            $webhookURL = substr($url, 0, 8) . $credentials . substr($url, 8) . '/hook/loqed/' . $this->InstanceID;
            $this->WriteAttributeString('WebHookURL', $webhookURL);
            $this->SendDebug(__FUNCTION__, 'WebHook URL: ' . $url, 0);
            $this->UpdateFormField('WebHookURL', 'caption', $webhookURL);
        }
    }
}