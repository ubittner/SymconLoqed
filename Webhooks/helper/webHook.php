<?php

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait Helper_webHook
{
    #################### Protected

    protected function ProcessHookData()
    {
        $timestamp = date('d.m.Y H:i:s');
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
        $smartLockData = json_decode($data, true);
        if (is_array($smartLockData) && !empty($smartLockData)) {
            if (array_key_exists('lock_id', $smartLockData)) {
                $lockIDold = $this->ReadPropertyString('LockIDold');
                if ($smartLockData['lock_id'] != $lockIDold) {
                    $this->SendDebug(__FUNCTION__, 'Abort, this data is not for this smart lock id!', 0);
                    return;
                }
            }
            if (array_key_exists('requested_state', $smartLockData)) {
                $state = $smartLockData['requested_state'];
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
                if (isset($smartLockValue)) {
                    $this->SetValue('SmartLock', $smartLockValue);
                }
                if (isset($deviceState)) {
                    $this->SetValue('DeviceState', $deviceState);
                }
                //Update log
                if (array_key_exists('key_name_user', $smartLockData)) {
                    $user = $smartLockData['key_name_user'];
                }
                if (array_key_exists('key_account_email', $smartLockData)) {
                    $eMail = $smartLockData['key_account_email'];
                }
                if (isset($user) && isset($action) && isset($eMail)) {
                    $this->UpdateActivityLog($timestamp, $action, $user, $eMail);
                }
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
        }
    }
}