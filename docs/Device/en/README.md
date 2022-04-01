[![Image](../../../imgs/LOQED_logo_20.png)](https://loqed.com)

### LOQED Device

This module integrates a [LOQED Smart Lock](https://loqed.com) into [IP-Symcon](https://www.symcon.de) via the local Bridge.  

The only lock that opens your door with a single tap.  

For this module there is no claim for further development, other support or can include errors.  
Before installing the module, a backup of IP-Symcon should be performed.  
The developer is not liable for any data loss or other damages.  
The user expressly agrees to the above conditions, as well as the license conditions.

### Table of contents

1. [Scope of functions](#1-scope-of-functions)
2. [Requirements](#2-requirements)
3. [Software installation](#3-software-installation)
4. [Setting up the instance](#4-setting-up-the-instance)
5. [Statevariables and profiles](#5-statevariables-and-profiles)
6. [WebFront](#6-webfront)
7. [PHP command reference](#7-php-command-reference)

### 1. Scope of functions

* Unlock, lock and open
* Display device status (various)
* Activity log

### 2. Requirements

- IP-Symcon at least version 6.0
- LOQED Smart Lock
- LOQED Bridge
- API configuratuion from [LOQED API Configuration Portal](https://app.loqed.com/API-Config/)

### 3. Software installation

* For commercial use (e.g. as an integrator), please contact the author first.
* Use the `Module Store` for installing the `Loqed`-Module.

### 4. Setting up the instance

- Use the `Loqed Discovery` instance to create the `Loqed Device` automatically.
- [Documentation Loqed Discovery](../../../docs/Discovery/en/README.md)

Manual setup:

- In IP-Symcon select `Add instance` at any place and select `Loqed Device` which is listed under the manufacturer `Loqed`.
- A new `Loqed Device` instance will be created.

__Configuration__:

Name                                | Beschreibung
----------------------------------- | ---------------------------------------
Device Name                         | Device name
LOQED Bridge IP Adresse             | IP-Address LOQED Bridge
LOQED Bridge Port                   | Port LOQED Bridge
Network Timeout                     | Netzwork Timeout
                                    |
Outgoing Webhooks via LOQED Bridge  |
Device Config Key                   | Device Config Key
Use automatic update                | automatic update for device state
IP-Symcon Host IP-Adresse           | IP-Address IP-Symcon
IP-Symcon Host Port                 | Port IP-Symcon
                                    |
API Keys (incoming webhooks)        |
Local Key ID                        | Local Key ID
Key                                 | Key
                                    |
Use daily lock                      | Use daily lock
Lock time                           | Lock time
Use daily unlock                    | Use daily unblock
Unlock time                         | Unlock time
                                    |
Use activity log                    | Use activity log
Number of maximum activity entries  | Maximum activity entries

__Buttons in the action area__:

Name                    | Description
----------------------- | --------------------------------
Developer area          |
Update device state     | Updates the state of the device
Webhooks                |
List                    | List webhooks
Delete                  | Delete Webhook ID

__Procedure__:

Please register first on the [LOQED API Configuration Portal](https://app.loqed.com/API-Config/) for the required data.  
Enter your data and then apply the changes.

### 5. Statevariables and profiles

The state variables/categories are created automatically.  
Deleting individual ones can lead to malfunctions.

##### Statusvariables

Name                            | Type    | Description
------------------------------- | ------- | --------------------------------
SmartLock                       | integer | Unlock, lock and open Smart Lock
OnlineState                     | integer | Online state
DeviceState                     | integer | Device state (various)
BatteryCharge                   | integer | Battery charge (in %)
BatteryType                     | integer | Battery type
ActivityLog                     | string  | Activity log

##### Profile:

LOQED.InstanceID.Name

Name                    | Type
----------------------- | -------
SmartLock               | integer
OnlineState             | integer
DeviceState             | integer
BatteryCharge           | integer
BatteryType             | integer

If the instance is deleted, the profiles listed above are automatically deleted.

### 6. WebFront

The functionality provided by the module in the WebFront:

[![Image](../../../imgs/Device/device_webfront_en.png)]()

[![Image](../../../imgs/Device/device_webfront_mobile_en.png)]()

* Unlock, lock and open
* Display device status (various)
* Activity log

### 7. PHP command reference

```text
Set smart lock action:  

LOQED_SetSmartLockAction(integer $InstanceID, int $Action);

Switches a specific action of the smart lock.  
Returns true if successful, false otherwise.  

$InstanceID:    Instance ID of the smart lock 
$Action:        Executes an action for the smart lock according to the table:  
```

Value   | Smart lock action            
------- | ----------------- 
0       | lock                         
1       | unlock 
2       | open 

```text
Example:  
//Lock smart lock
$setAction = LOQED_SetSmartLockAction(12345, 0); 
//Outputs the return value
echo $setAction;      

//Unlock smart lock
$setAction = LOQED_SetSmartLockAction(12345, 1);
//Outputs the return value
echo $setAction;      
```

```text
Update device state:  

LOQED_UpdateDeviceState(integer $InstanzID);  

Queries the current status of the smart lock and updates the values of the corresponding variables.  
Returns true if successful, false otherwise. 

Example:  
LOQED_UpdateDeviceState(12345);  
```  