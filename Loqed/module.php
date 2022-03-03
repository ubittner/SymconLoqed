<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class Loqed extends IPSModule
{
    //Helper
    use Helper_webHook;
    //Constants
    private const LIBRARY_GUID = '{9AC35841-9778-D8E8-31CF-0BDAD3E0C3A7}';
    private const MODULE_PREFIX = 'LOQED';
    private const CORE_WEBHOOK_GUID = '{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}';
    private const CORE_CONNECT_GUID = '{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties
        $this->RegisterPropertyString('LockIDold', '');
        $this->RegisterPropertyString('APIKey', '');
        $this->RegisterPropertyString('APIToken', '');
        $this->RegisterPropertyString('LocalKeyID', '');
        $this->RegisterPropertyString('LockID', '');
        $this->RegisterPropertyString('DailyUpdateTime', '{"hour":12,"minute":0,"second":0}');

        ########## Variables
        //Smart Lock
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SmartLock';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Lock'), 'LockClosed', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Unlock'), 'LockOpen', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Open'), 'Door', 0x0000FF);
        $this->RegisterVariableInteger('SmartLock', 'Smart Lock', $profile, 100);
        $this->EnableAction('SmartLock');

        //Online state: bridge_online (1 if bridge is online, otherwise 0)
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.OnlineState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Offline'), 'Warning', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Online'), 'Network', 0x00FF00);
        $this->RegisterVariableInteger('OnlineState', $this->Translate('Online state'), $profile, 200);

        //Device state: bolt_state_numeric (0 = unknown, 1 = open, 2 = day_lock, 3 = night_lock)
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.DeviceState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Unknown'), 'Warning', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Opened'), 'Door', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Unlocked'), 'LockOpen', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, $this->Translate('Locked'), 'LockClosed', 0xFF0000);
        $this->RegisterVariableInteger('DeviceState', $this->Translate('Device state'), $profile, 210);

        //Battery charge: battery_percentage
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryCharge';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileValues($profile, 0, 100, 1);
        IPS_SetVariableProfileText($profile, '', '%');
        IPS_SetVariableProfileIcon($profile, 'Battery');
        $this->RegisterVariableInteger('BatteryCharge', $this->Translate('Battery charge'), $profile, 220);

        //Battery type: battery_type (0 = Alkaline, 1 = NiMH, 2 = Lithium (non-rechargable))
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.BatteryType';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Battery');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Alkaline'), '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('NiMH'), '', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, $this->Translate('Lithium (non-rechargable)'), '', 0x0000FF);
        $this->RegisterVariableInteger('BatteryType', $this->Translate('Battery type'), $profile, 230);

        //Guest access: guest_access_mode (1 if enabled, 0 if disabled)
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.GuestAccess';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Motion');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Disabled'), '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Enabled'), '', 0xFF0000);
        $this->RegisterVariableInteger('GuestAccess', $this->Translate('Guest access'), $profile, 240);

        //Twist assist: twist_assist (1 if enabled, 0 if disabled)
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.TwistAssist';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Repeat');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Disabled'), '', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Enabled'), '', 0xFF0000);
        $this->RegisterVariableInteger('TwistAssist', $this->Translate('Twist assist'), $profile, 250);

        //Touch To Open: touch_to_connect (1 if Touch to Open 500-meter restriction is removed, 0 otherwise)
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.TouchToOpen';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Execute');
        IPS_SetVariableProfileAssociation($profile, 0, $this->Translate('Restricted to 500m'), '', 0xF00F00);
        IPS_SetVariableProfileAssociation($profile, 1, $this->Translate('Restriction is removed'), '', 0x00FF00);
        $this->RegisterVariableInteger('TouchToOpen', $this->Translate('Touch to Open'), $profile, 260);

        ########## Attributes
        $this->RegisterAttributeString('WebHookURL', '');
        $this->RegisterAttributeString('WebHookUser', '');
        $this->RegisterAttributeString('WebHookPassword', '');

        ########## Timer
        $this->RegisterTimer('DailyUpdate', 0, self::MODULE_PREFIX . '_UpdateDeviceState(' . $this->InstanceID . ');');
    }

    public function Destroy()
    {
        //Unregister WebHook
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterWebhook('/hook/loqed/' . $this->InstanceID);
        }

        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['SmartLock', 'OnlineState', 'DeviceState', 'BatteryCharge', 'BatteryType', 'GuestAccess', 'TwistAssist', 'TouchToOpen'];
        foreach ($profiles as $profile) {
            $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profile)) {
                IPS_DeleteVariableProfile($profile);
            }
        }
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check kernel runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //WebHook
        $this->PrepareWebHook();
        $this->RegisterWebHook('/hook/loqed/' . $this->InstanceID);

        //Validate configuration
        if (!$this->ValidateConfiguration()) {
            return;
        }

        $this->UpdateDeviceState();
        $this->SetDailyUpdateTimer();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $formData['elements'][2]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ' vom ' . date('d.m.Y', $library['Date']);
        $formData['actions'][0]['value'] = $this->ReadAttributeString('WebHookURL');
        return json_encode($formData);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value): void
    {
        switch ($Ident) {
            case 'SmartLock':
                $this->SetSmartLockAction($Value);
                break;

        }
    }

    #################### Public

    public function SetSmartLockAction(int $Action): bool
    {
        $lockIDold = $this->ReadPropertyString('LockIDold');
        $apiKey = $this->ReadPropertyString('APIKey');
        $apiToken = $this->ReadPropertyString('APIToken');
        $localKeyID = $this->ReadPropertyString('LocalKeyID');
        if (empty($lockIDold) || empty($apiKey) || empty($apiToken) || empty($localKeyID)) {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return false;
        }
        $this->SetValue('SmartLock', $Action);
        switch ($Action) {
            case 0: # Lock
                $lockAction = 'NIGHT_LOCK';
                break;

            case 1: # Unlock
                $lockAction = 'DAY_LOCK';
                break;

            case 2: # Open
                $lockAction = 'OPEN';
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Unknown action: ' . $Action, 0);
                return false;
        }
        $success = false;
        $endpoint = 'https://gateway.production.loqed.com/v1/locks/' . urlencode($lockIDold) . '/state?lock_api_key=' . urlencode($apiKey) . '&api_token=' . urlencode($apiToken) . '&lock_state=' . $lockAction . '&local_key_id=' . urlencode($localKeyID);
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST   => 'GET',
            CURLOPT_URL             => $endpoint,
            CURLOPT_HEADER          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => false,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_TIMEOUT         => 60]);
        $response = curl_exec($curl);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $body = json_decode(substr($response, $header_size), true);
            $this->SendDebug(__FUNCTION__, 'Response body: ' . json_encode($body), 0);
            switch ($httpCode) {
                case 200:  # OK
                    $success = true;
                    break;

            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($curl);
        return $success;
    }

    public function UpdateDeviceState(): bool
    {
        $this->SetDailyUpdateTimer();
        $apiToken = $this->ReadPropertyString('APIToken');
        $lockID = $this->ReadPropertyString('LockID');
        if (empty($apiToken) || empty($lockID)) {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return false;
        }
        $success = false;
        $endpoint = 'https://app.loqed.com/API/lock_status.php?api_token=' . urlencode($apiToken) . '&lock_id=' . $lockID;
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST   => 'GET',
            CURLOPT_URL             => $endpoint,
            CURLOPT_HEADER          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => false,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_TIMEOUT         => 60]);
        $response = curl_exec($curl);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $smartLockData = json_decode(substr($response, $header_size), true);
            $this->SendDebug(__FUNCTION__, 'Response body: ' . json_encode($smartLockData), 0);
            switch ($httpCode) {
                case 200:  # OK
                    if (is_array($smartLockData) && !empty($smartLockData)) {
                        if (array_key_exists('id', $smartLockData)) {
                            $lockID = $this->ReadPropertyString('LockID');
                            if ($smartLockData['id'] != $lockID) {
                                $this->SendDebug(__FUNCTION__, 'Abort, this data is not for this smart lock id!', 0);
                                return $success;
                            }
                        }
                        if (array_key_exists('bolt_state_numeric', $smartLockData)) {
                            $success = true;
                            $this->SetValue('DeviceState', $smartLockData['bolt_state_numeric']);
                        }
                        if (array_key_exists('bridge_online', $smartLockData)) {
                            $success = true;
                            $this->SetValue('OnlineState', $smartLockData['bridge_online']);
                        }
                        if (array_key_exists('battery_percentage', $smartLockData)) {
                            $success = true;
                            $this->SetValue('BatteryCharge', $smartLockData['battery_percentage']);
                        }
                        if (array_key_exists('battery_type', $smartLockData)) {
                            $success = true;
                            $this->SetValue('BatteryType', $smartLockData['battery_type']);
                        }
                        if (array_key_exists('guest_access_mode', $smartLockData)) {
                            $success = true;
                            $this->SetValue('GuestAccess', $smartLockData['guest_access_mode']);
                        }
                        if (array_key_exists('twist_assist', $smartLockData)) {
                            $success = true;
                            $this->SetValue('TwistAssist', $smartLockData['twist_assist']);
                        }
                        if (array_key_exists('touch_to_connect', $smartLockData)) {
                            $success = true;
                            $this->SetValue('TouchToOpen', $smartLockData['touch_to_connect']);
                        }
                    }
                    break;

            }
        } else {
            $error_msg = curl_error($curl);
            $this->SendDebug(__FUNCTION__, 'An error has occurred: ' . json_encode($error_msg), 0);
        }
        curl_close($curl);
        return $success;
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): bool
    {
        $status = 102;
        $result = true;
        $lockIDold = $this->ReadPropertyString('LockIDold');
        $apiKey = $this->ReadPropertyString('APIKey');
        $apiToken = $this->ReadPropertyString('APIToken');
        $localKeyID = $this->ReadPropertyString('LocalKeyID');
        $lockID = $this->ReadPropertyString('LockID');
        if (empty($lockIDold) || empty($apiKey) || empty($apiToken) || empty($localKeyID) || empty($lockID)) {
            $status = 201;
        }
        $this->SetStatus($status);
        return $result;
    }

    private function SetDailyUpdateTimer(): void
    {
        $now = time();
        $updateTime = json_decode($this->ReadPropertyString('DailyUpdateTime'));
        $hour = $updateTime->hour;
        $minute = $updateTime->minute;
        $second = $updateTime->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        $this->SetTimerInterval('DailyUpdate', ($timestamp - $now) * 1000);
    }
}