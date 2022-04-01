<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

class LoqedDiscovery extends IPSModule
{
    //Constants
    private const LIBRARY_GUID = '{9AC35841-9778-D8E8-31CF-0BDAD3E0C3A7}';
    private const CORE_DNS_SD_GUID = '{780B2D48-916C-4D59-AD35-5A429B2355A5}';
    private const LOQED_DEVICE_GUID = '{5ED9958D-1D89-F620-7CEF-FF38B8FDD201}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyInteger('CategoryID', 0);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();
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
        $values = [];
        $existingDevices = $this->DiscoverDevices();
        if (!empty($existingDevices)) {
            foreach ($existingDevices as $device) {
                $instanceID = $this->GetDeviceInstanceID($device['name']);
                $location = $this->GetCategoryPath($this->ReadPropertyInteger(('CategoryID')));
                $values[] = [
                    'BridgeIP'    => $device['ip'],
                    'BridgePort'  => $device['port'],
                    'BridgeName'  => $device['name'],
                    'instanceID'  => $instanceID,
                    'create'      => [
                        'moduleID'      => self::LOQED_DEVICE_GUID,
                        'name'          => $device['name'],
                        'configuration' => [
                            'DeviceName'   => (string) $device['name'],
                            'BridgeIP'     => (string) $device['ip'],
                            'BridgePort'   => (int) $device['port']
                        ],
                        'location' => $location
                    ]
                ];
            }
        }
        $formData['actions'][0]['values'] = $values;
        return json_encode($formData);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function GetCategoryPath(int $CategoryID): array
    {
        if ($CategoryID === 0) {
            return [];
        }
        $path[] = IPS_GetName($CategoryID);
        $parentID = IPS_GetObject($CategoryID)['ParentID'];
        while ($parentID > 0) {
            $path[] = IPS_GetName($parentID);
            $parentID = IPS_GetObject($parentID)['ParentID'];
        }
        return array_reverse($path);
    }

    private function DiscoverDevices(): array
    {
        $ids = IPS_GetInstanceListByModuleID(self::CORE_DNS_SD_GUID);
        $devices = ZC_QueryServiceType($ids[0], '_http._tcp', '');
        $this->SendDebug(__FUNCTION__, 'Query service type: ' . print_r($devices, true), 0);
        $existingDevices = [];
        if (!empty($devices)) {
            foreach ($devices as $device) {
                $data = [];
                $deviceInfos = ZC_QueryService($ids[0], $device['Name'], '_http._tcp.', 'local.');
                $this->SendDebug(__FUNCTION__, 'Query service: ' . print_r($deviceInfos, true), 0);
                if (!empty($deviceInfos)) {
                    foreach ($deviceInfos as $info) {
                        $name = $info['Name'];
                        if (substr($name, 0, 5) == 'LOQED') {
                            $data['name'] = str_replace('._http._tcp.local', '', $info['Name']);
                            if (empty($info['IPv4'])) {
                                $data['ip'] = $info['IPv6'][0];
                            } else {
                                $data['ip'] = $info['IPv4'][0];
                            }
                            $data['port'] = $info['Port'];
                            array_push($existingDevices, $data);
                        }
                    }
                }
            }
        }
        return $existingDevices;
    }

    private function GetDeviceInstanceID(string $DeviceName): int
    {
        $instanceID = 0;
        $instanceIDs = IPS_GetInstanceListByModuleID(self::LOQED_DEVICE_GUID);
        foreach ($instanceIDs as $id) {
            if (IPS_GetProperty($id, 'DeviceName') == $DeviceName) {
                $instanceID = $id;
            }
        }
        return $instanceID;
    }
}