# Project #
A feature rich Board for managing daily duties in the Hotel business. For customization, please mail: tow.berlin@gmail.com

## Features ##
- Daily/Monthly Statistics
- Rateplan
- Competitor Check
- Budget Planning
- PDF Exports for RateSheet and Daily TaxForms
- CityTax Calculator (Berlin)
- Import Reports directly from Opera (XML)

# Installation ##
## Prerequisites ##
You need a webserver (apache or nginx or whatever you prefer) and composer installed.

## Steps ##
- `git clone https://github.com/primus852/HotelControlBoard`
- `cd HotelControlBoard && composer install`
- adjust DB Settings in `.env` or you enviroment variables for your webserver.
- Create SuperAdmin User `php bin/console fos:user:create testuser test@example.com p@ssword --super-admin`
- Create additional Users `php bin/console fos:user:create normaluser normal@example.com p@ssword`
- Users that should be able to edit the Budget should have `ROLE_MANAGER` as well: `php bin/console fos:user:promote normaluser ROLE_MANAGER`
- For furher user management see [FOSUserBundle Command Line Tools](https://symfony.com/doc/2.0/bundles/FOSUserBundle/command_line_tools.html)
- go to `http://ip-of-your-server/` and login

# Screenshots #
![alt text](https://raw.githubusercontent.com/primus852/HotelControlBoard/master/public/assets/screens/dashboard_0.7.1.jpg "Dashboard")
Dashboard

![alt text](https://raw.githubusercontent.com/primus852/HotelControlBoard/master/public/assets/screens/rateplan_0.7.1.jpg "Rateoverview")
Rateoverview

![alt text](https://raw.githubusercontent.com/primus852/HotelControlBoard/master/public/assets/screens/budget_0.7.1.jpg "Ratetypes")
Ratetypes


# Changelog #

## 0.7.1 ##
- adjusted colors for Rateplan
- added Screenshots

## 0.7.0 ##
- added more Options to Ratetypes
- added RateQuery to Dashboard

## 0.6.2 ##
- updated Dashboard
- fixed missing BF in Accomodation monthly
- highered precision in DB for floats

## 0.6.1 ##
- added Roomnights to monthly Stats & Budget

## 0.6.0 ##
- Added Budget Setting
- Added Budget (daily/monthly) to Dashboard
- Weatherforecast
- CityTax Calculator

## 0.5.1 ##
- added City Tax Forms generator
- fixed Permission Issues for USER

## 0.5.0 ##
- Initial Release

# ToDo before Release #
- Custom Logo Setting for Sheets
- Settings for CityTax
- Style continuity
- In-Panel Usermanagement (not via commandline)
- Update the ReadMe and create demo page
