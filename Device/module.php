<?php

/** @noinspection DuplicatedCode */
/** @noinspection HttpUrlsUsage */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class LoqedDevice extends IPSModule
{
    //Helper
    use Webhooks;

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
        $this->RegisterPropertyString('DeviceName', '');
        $this->RegisterPropertyString('BridgeIP', '');
        $this->RegisterPropertyInteger('BridgePort', 80);
        $this->RegisterPropertyInteger('Timeout', 5000);
        $this->RegisterPropertyBoolean('UseWebhook', false);
        $this->RegisterPropertyString('HostIP', (count(Sys_GetNetworkInfo()) > 0) ? Sys_GetNetworkInfo()[0]['IP'] : '');
        $this->RegisterPropertyInteger('HostPort', 3777);
        $this->RegisterPropertyString('LocalKeyID', '');
        $this->RegisterPropertyString('Key', '');
        $this->RegisterPropertyString('DeviceConfigKey', '');
        $this->RegisterPropertyBoolean('UseDailyLock', false);
        $this->RegisterPropertyString('DailyLockTime', '{"hour":23,"minute":0,"second":0}');
        $this->RegisterPropertyBoolean('UseDailyUnlock', false);
        $this->RegisterPropertyString('DailyUnlockTime', '{"hour":6,"minute":0,"second":0}');
        $this->RegisterPropertyBoolean('UseActivityLog', false);
        $this->RegisterPropertyInteger('ActivityLogMaximumEntries', 10);

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

        //Online state: lock_online (1 if lock is online, otherwise 0)
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

        ########## Attributes
        $this->RegisterAttributeString('WebhookUser', '');
        $this->RegisterAttributeString('WebhookPassword', '');

        ########## Timer
        $this->RegisterTimer('DailyLock', 0, self::MODULE_PREFIX . '_SetSmartLockAction(' . $this->InstanceID . ', 0);');
        $this->RegisterTimer('DailyUnlock', 0, self::MODULE_PREFIX . '_SetSmartLockAction(' . $this->InstanceID . ', 1);');
    }

    public function Destroy()
    {
        //Unregister WebHook
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterWebhook('/hook/loqed/device/' . $this->InstanceID);
        }

        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['SmartLock', 'OnlineState', 'DeviceState', 'BatteryCharge', 'BatteryType'];
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

        ########## Maintain variable

        //Activity log
        if ($this->ReadPropertyBoolean('UseActivityLog')) {
            $id = @$this->GetIDForIdent('ActivityLog');
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, 'HTMLBox', 300, true);
            if ($id == false) {
                IPS_SetIcon($this->GetIDForIdent('ActivityLog'), 'Database');
            }
        } else {
            $this->MaintainVariable('ActivityLog', $this->Translate('Activity log'), 3, '', 0, false);
        }

        $this->RegisterWebhook('/hook/loqed/device/' . $this->InstanceID);
        $this->GenerateWebhookCredentials();

        //Validate configuration
        if (!$this->ValidateConfiguration()) {
            return;
        }

        $this->ManageBridgeWebhooks();
        $this->UpdateDeviceState();
        $this->SetDailyLockTimer();
        $this->SetDailyUnlockTimer();
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
        $formData['elements'][1]['caption'] = 'ID: ' . $this->InstanceID . ', Version: ' . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date']);
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
        $this->SetDailyLockTimer();
        $this->SetDailyUnlockTimer();
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        $localKeyID = $this->ReadPropertyString('LocalKeyID');
        $key = $this->ReadPropertyString('Key');
        if ($bridgeIP == '' || $localKeyID == '' || $key == '') {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return false;
        }
        $this->SetValue('SmartLock', $Action);
        switch ($Action) {
            case 0: # Lock
                $lockAction = 3; # NIGHT_LOCK
                break;

            case 1: # Unlock
                $lockAction = 2; # DAY_LOCK
                break;

            case 2: # Open
                $lockAction = 1; # OPEN
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Unknown action: ' . $Action, 0);
                return false;
        }
        $success = false;
        $messageID = 0; // Normally backend generates this and lock returns it for some commands when responding to a command. Not applicable for local network applications.
        $protocol = 2;
        $commandType = 7;
        $deviceID = 1;
        //Build binary string
        $binaryMessageID = pack('J', $messageID);
        $binaryProtocol = pack('C', $protocol);
        $binaryCommandType = pack('C', $commandType);
        $binaryLocalKeyID = pack('C', $localKeyID);
        $binaryDeviceID = pack('C', $deviceID);
        $binaryTimeNow = pack('J', time());
        $binaryAction = pack('C', $lockAction);
        //Build hash
        $binaryLocalGeneratedHash = $binaryProtocol . $binaryCommandType . $binaryTimeNow . $binaryLocalKeyID . $binaryDeviceID . $binaryAction;
        $binaryLocalGeneratedHash = hash_hmac('sha256', $binaryLocalGeneratedHash, base64_decode($key), true);
        //Build command
        $command = '';
        $command .= $binaryMessageID;
        $command .= $binaryProtocol;
        $command .= $binaryCommandType;
        $command .= $binaryTimeNow;
        $command .= $binaryLocalGeneratedHash;
        $command .= $binaryLocalKeyID;
        $command .= $binaryDeviceID;
        $command .= $binaryAction;
        $command = base64_encode($command);
        //Send data
        $endpoint = 'http://' . $bridgeIP . ':' . $this->ReadPropertyInteger('BridgePort') . '/to_lock?command_signed_base64=' . urlencode($command);
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST     => 'GET',
            CURLOPT_URL               => $endpoint,
            CURLOPT_HEADER            => true,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_FAILONERROR       => false,
            CURLOPT_CONNECTTIMEOUT_MS => $this->ReadPropertyInteger('Timeout'),
            CURLOPT_TIMEOUT           => 30]);
        $response = curl_exec($curl);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $body = substr($response, $header_size);
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
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        if ($bridgeIP == '') {
            $this->SendDebug(__FUNCTION__, 'Please check your configuration!', 0);
            return false;
        }
        $success = false;
        $endpoint = 'http://' . $bridgeIP . ':' . $this->ReadPropertyInteger('BridgePort') . '/status';
        $this->SendDebug(__FUNCTION__, 'Endpoint: ' . $endpoint, 0);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST     => 'GET',
            CURLOPT_URL               => $endpoint,
            CURLOPT_HEADER            => true,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_FAILONERROR       => false,
            CURLOPT_CONNECTTIMEOUT_MS => $this->ReadPropertyInteger('Timeout'),
            CURLOPT_TIMEOUT           => 60]);
        $response = curl_exec($curl);
        $this->SendDebug(__FUNCTION__, 'Response: ' . $response, 0);
        if (!curl_errno($curl)) {
            $httpCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $this->SendDebug(__FUNCTION__, 'Response http code: ' . $httpCode, 0);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $this->SendDebug(__FUNCTION__, 'Response header: ' . $header, 0);
            $bridgeData = json_decode(substr($response, $header_size), true);
            $this->SendDebug(__FUNCTION__, 'Response body: ' . json_encode($bridgeData), 0);
            switch ($httpCode) {
                case 200:  # OK
                    if (is_array($bridgeData) && !empty($bridgeData)) {
                        if (array_key_exists('bolt_state_numeric', $bridgeData)) {
                            $success = true;
                            $boltState = $bridgeData['bolt_state_numeric'];
                            $this->SetValue('DeviceState', $boltState);
                            switch ($boltState) {
                                case 1: # open
                                    $this->SetValue('SmartLock', 2); # open
                                    break;

                                case 2: # day_lock
                                    $this->SetValue('SmartLock', 1); # unlock
                                    break;

                                case 3: # night_lock
                                    $this->SetValue('SmartLock', 0); # lock
                                    break;
                            }
                        }
                        if (array_key_exists('lock_online', $bridgeData)) {
                            $success = true;
                            $this->SetValue('OnlineState', $bridgeData['lock_online']);
                        }
                        if (array_key_exists('battery_percentage', $bridgeData)) {
                            $success = true;
                            $this->SetValue('BatteryCharge', $bridgeData['battery_percentage']);
                        }
                        if (array_key_exists('battery_type_numeric', $bridgeData)) {
                            $success = true;
                            $this->SetValue('BatteryType', $bridgeData['battery_type_numeric']);
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
        $bridgeIP = $this->ReadPropertyString('BridgeIP');
        $localKeyID = $this->ReadPropertyString('LocalKeyID');
        $key = $this->ReadPropertyString('Key');
        if ($bridgeIP == '' || $localKeyID == '' || $key == '') {
            $status = 201;
        }
        $this->SetStatus($status);
        return $result;
    }

    private function SetDailyLockTimer(): void
    {
        $now = time();
        $lockTime = json_decode($this->ReadPropertyString('DailyLockTime'));
        $hour = $lockTime->hour;
        $minute = $lockTime->minute;
        $second = $lockTime->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        $interval = ($timestamp - $now) * 1000;
        if (!$this->ReadPropertyBoolean('UseDailyLock')) {
            $interval = 0;
        }
        $this->SetTimerInterval('DailyLock', $interval);
    }

    private function SetDailyUnlockTimer(): void
    {
        $now = time();
        $unlockTime = json_decode($this->ReadPropertyString('DailyUnlockTime'));
        $hour = $unlockTime->hour;
        $minute = $unlockTime->minute;
        $second = $unlockTime->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        $interval = ($timestamp - $now) * 1000;
        if (!$this->ReadPropertyBoolean('UseDailyUnlock')) {
            $interval = 0;
        }
        $this->SetTimerInterval('DailyUnlock', $interval);
    }

    private function UpdateActivityLog(string $TimeStamp, string $Action, string $Key): void
    {
        if (!$this->ReadPropertyBoolean('UseActivityLog')) {
            $this->SendDebug(__FUNCTION__, 'Abort, activity log is disabled', 0);
            return;
        }
        $string = $this->GetValue('ActivityLog');
        if (empty($string)) {
            $entries = [];
            $this->SendDebug(__FUNCTION__, 'String is empty!', 0);
        } else {
            $entries = explode('<tr><td>', $string);
            //Remove header
            foreach ($entries as $key => $entry) {
                if ($entry == "<table style='width: 100%; border-collapse: collapse;'><tr> <td><b>" . $this->Translate('Date') . '</b></td> <td><b>' . $this->Translate('Action') . '</b></td> <td><b>' . $this->Translate('Local Key ID') . '</b></td> </tr>') {
                    unset($entries[$key]);
                }
            }
            //Remove table
            foreach ($entries as $key => $entry) {
                $position = strpos($entry, '</table>');
                if ($position > 0) {
                    $entries[$key] = str_replace('</table>', '', $entry);
                }
            }
        }
        array_unshift($entries, $TimeStamp . '</td><td>' . $Action . '</td><td>' . $Key . '</td></tr>');
        $maximumEntries = $this->ReadPropertyInteger('ActivityLogMaximumEntries') - 1;
        foreach ($entries as $key => $entry) {
            if ($key > $maximumEntries) {
                unset($entries[$key]);
            }
        }
        $newString = "<table style='width: 100%; border-collapse: collapse;'><tr> <td><b>" . $this->Translate('Date') . '</b></td> <td><b>' . $this->Translate('Action') . '</b></td> <td><b>' . $this->Translate('Local Key ID') . '</b></td> </tr>';
        foreach ($entries as $entry) {
            $newString .= '<tr><td>' . $entry;
        }
        $newString .= '</table>';
        $this->SendDebug(__FUNCTION__, 'New string: ' . $newString, 0);
        $this->SetValue('ActivityLog', $newString);
    }
}