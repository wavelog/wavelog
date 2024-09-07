![GitHub License](https://img.shields.io/github/license/wavelog/wavelog)
[![Übersetzungsstatus](https://translate.wavelog.org/widget/wavelog/main-translation/svg-badge.svg)](https://translate.wavelog.org/engage/wavelog/)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/wavelog/wavelog)


# Wavelog

> Important: Only accepting PRs on the "dev" branch.

Wavelog is a self-hosted PHP application that allows you to log your amateur radio contacts anywhere. All you need is a web browser and active internet connection.

Wavelog itself is an enhanced fork of Cloudlog by 2M0SQL.
If you would like to contribute to Wavelog please see the [Contributing](#contributing) section below.

The Core-Dev-Team for Wavelog are (in Alphabetic order of the Call):
* DF2ET ([@phl0](https://github.com/phl0))
* DJ7NT ([@int2001](https://github.com/int2001))
* HB9HIL ([@HB9HIL](https://github.com/HB9HIL))
* LA8AJA ([@AndreasK79](https://github.com/AndreasK79))

## Demo
Test Wavelog and it's features!  
https://demo.wavelog.org  

  Username: demo  
  Password: demo


## Requirements
### Recommended: Classic-LAMP-Stack:
* Linux based Operating System (Windows stack may also work)
* Apache (Nginx should work)
* PHP Version 7.4 up to PHP 8.2 (PHP 8.3. in friendly Usertest)
* MySQL or MariaDB (MySQL 5.7 or higher // MariaDB 10.1 or higher)

### Alternative - Easy start with prebuilt Docker-image:
* [Docker Support](https://github.com/wavelog/wavelog/wiki/Installation-via-Docker)

Notes
* If you want to log microwave QSOs you will need to use a 64bit operating system.

## Setup

Installation information can be found on the [wiki](https://github.com/wavelog/wavelog/wiki).

## Support

Wavelog-support can be reached by creating an issue here at github. If you've any issues don't hesitate to create one here. Please provide as much information as possible to reproduce the Problem


## Contributing

Special thanks to our contributors, who are part of Wavelog by improving code!

[F4ANS](https://github.com/abarrau), [DG0TM](https://github.com/dg0tm), [DG9VH](https://github.com/dg9vh), [DJ3CE](https://github.com/dj3ce), [R1BLH](https://github.com/r1blh), [BG2ELG](https://github.com/violarulan), [DF1ASH](https://github.com/derFogel), [DB4SCW](https://github.com/DB4SCW), [VE2HEW](https://github.com/anthonydiiorio), [OK1GOD](https://github.com/filipmelik), [DJ1PW](https://github.com/winnieXY), [toseppo](https://github.com/toseppo), [N7DSB](https://github.com/desertblade), [BA7LAC](https://github.com/imlonghao)

Translators:

[Ondřej Koloničný (OK1CDJ)](https://translate.wavelog.org/user/ok1cdj/), [Michael Skolsky (R1BLH)](https://translate.wavelog.org/user/R1BLH/), [Karuru (BG2ELG)](https://translate.wavelog.org/user/viola/), [Byt3](https://translate.wavelog.org/user/205er/), [BG6HJE](https://translate.wavelog.org/user/BG6HJE/), [Francisco (F4VSE)](https://translate.wavelog.org/user/kikosgc/), [Kim (DG9VH)](https://translate.wavelog.org/user/dg9vh/), [Casper van Lieburg (PA7DX)](https://translate.wavelog.org/user/pa7dx/), [Halil AYYILDIZ (TA2LG)](https://translate.wavelog.org/user/TA2LG/), [Michal Šiman](https://translate.wavelog.org/user/michalsiman/), [DN4BS](https://github.com/dn4bs), [Luca (IU2FRL)](https://translate.wavelog.org/user/iu2frl/), [Dragan Đorđević (4O4A)](https://translate.wavelog.org/user/4o4a/), [Dren Imeraj (Z63DRI)](https://translate.wavelog.org/user/Dren/), [Filip Melik (OK1GOD)](https://translate.wavelog.org/user/filipmelik/), [Petr (OK1PTR)](https://translate.wavelog.org/user/OK1PTR/), [Stefan (DB4SCW)](https://translate.wavelog.org/user/DB4SCW/), [F4JSU](https://translate.wavelog.org/user/F4JSU/), [Maciej](https://translate.wavelog.org/user/maciejla/)

If you would like to contribute in any way to Wavelog, it is most appreciated. This has been developed in free time, help coding new features or writing documentation is always useful.  

**For translations and language stuff you can refer to our [Wiki about Translations](https://github.com/wavelog/wavelog/wiki/Translations).**

Please note that Wavelog was built using [Codeigniter](https://www.codeigniter.com/docs) version 3 and uses Bootstrap 5 for the user CSS framework documentation is available for this when building components.

When submitting PRs please make sure code is commented and includes one feature only, multiple features or bug fixes will not be accepted. Please include a description of what your PR does and why it is needed.
