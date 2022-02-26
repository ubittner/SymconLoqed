<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class Loqed extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{9AC35841-9778-D8E8-31CF-0BDAD3E0C3A7}';
    private const MODULE_PREFIX = 'LOQED';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('APIKey', '');
        $this->RegisterPropertyString('APIToken', '');
        $this->RegisterPropertyString('LocalKeyID', '');

        ########## Variable
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
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['SmartLock'];
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
        $this->ValidateConfiguration();
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
        $deviceID = $this->ReadPropertyString('DeviceID');
        $apiKey = $this->ReadPropertyString('APIKey');
        $apiToken = $this->ReadPropertyString('APIToken');
        $localKeyID = $this->ReadPropertyString('LocalKeyID');
        if (empty($deviceID) || empty($apiKey) || empty($apiToken) || empty($localKeyID)) {
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
        $endpoint = 'https://gateway.production.loqed.com/v1/locks/' . urlencode($deviceID) . '/state?lock_api_key=' . urlencode($apiKey) . '&api_token=' . urlencode($apiToken) . '&lock_state=' . $lockAction . '&local_key_id=' . urlencode($localKeyID);
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

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): void
    {
        $status = 102;
        $deviceID = $this->ReadPropertyString('DeviceID');
        $apiKey = $this->ReadPropertyString('APIKey');
        $apiToken = $this->ReadPropertyString('APIToken');
        $localKeyID = $this->ReadPropertyString('LocalKeyID');
        if (empty($deviceID) || empty($apiKey) || empty($apiToken) || empty($localKeyID)) {
            $status = 201;
        }
        $this->SetStatus($status);
    }
}