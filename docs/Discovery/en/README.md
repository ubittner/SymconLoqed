[![Image](../../../imgs/LOQED_logo_20.png)](https://loqed.com)

### LOQED Discovery

This module discovers existing LOQED devices.

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

* Discovers existing LOQED devices.
* Automatic creation of the selected devices

### 2. Requirements

- IP-Symcon at least version 6.0
- LOQED Smart Lock
- LOQED Bridge

### 3. Software installation

* For commercial use (e.g. as an integrator), please contact the author first.
* Use the `Module Store` for installing the `Loqed`-Module.

### 4. Setting up the instance

- In IP-Symcon select `Add instance` at any place and select `Loqed Discovery` which is listed under the manufacturer `Loqed`.
- A new `Loqed Discovery` instance will be created.

__Configuration__:

Name        | Description
----------- | -----------------------------
Category    | category for the devices
Devices     | list of the available devices

__Buttons in the action area__:

Name        | Description
------------| ---------------------------------------------------
Create all  | Creates one instance for each of the listed devices
Create      | Creates an instance for the selected device

__Procedure__:

You can use the `UPDATE` button to update the list of available devices at any time.  
Select `CREATE ALL` or select a device from the list and then press the `CREATE` button to create the device automatically.

Then enter the required data in the `Loqed Device`.

### 5. Statevariables and profiles

The state variables/categories are created automatically.  
Deleting individual ones can lead to malfunctions.

#### Status variables

No status variables are used.

#### Profiles

No profiles are used.

### 6. WebFront

The Discovery instance has no functionality in WebFront.

### 7. PHP command reference

There is no command reference available.