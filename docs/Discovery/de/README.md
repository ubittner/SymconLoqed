[![Image](../../../imgs/LOQED_logo_20.png)](https://loqed.com)

### LOQED Discovery

Dieses Modul erkennt vorhandene LOQED Geräte.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Erkennt vorhandene LOQED Geräte.
* Automatisches Anlegen der ausgewählten Geräte

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- LOQED Smart Lock
- LOQED Bridge

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Über den Module Store das `Loqed`-Modul installieren.

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Loqed Discovery` auswählen, welches unter dem Hersteller `Loqed` aufgeführt ist.
- Es wird eine neue `Loqed Discovery` Instanz angelegt.


__Konfigurationsseite__:

| Name      | Beschreibung               |
|-----------|----------------------------|
| Kategorie | Kategorie                  |
| Geräte    | Liste der erkannten Geräte |

__Schaltflächen__:

| Name           | Beschreibung                                                |
|----------------|-------------------------------------------------------------|
| Alle erstellen | Erstellt für alle aufgelisteten Geräte jeweils eine Instanz |
| Erstellen      | Erstellt für das ausgewählte Gerät eine Instanz             |

__Vorgehensweise__:

Über die Schaltfläche `AKTUALISIEREN` können Sie die Liste der verfügbaren Geräte jederzeit aktualisieren.  
Wählen Sie `ALLE ERSTELLEN` oder wählen ein Gerät aus der Liste aus und drücken dann die Schaltfläche `ERSTELLEN`, um das Gerät automatisch anzulegen.

Geben Sie anschließend im `Loqed Device` die erforderlichen Daten an.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Es werden keine Statusvariablen verwendet.

#### Profile

Es werden keine Profile verwendet.

### 6. WebFront

Die Discovery Instanz hat im WebFront keine Funktionalität.

### 7. PHP-Befehlsreferenz

Es ist keine Befehlsreferenz verfügbar.