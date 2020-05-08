# ViperLab

---

## Introduction

Grab your environment files from [ViperLab](https://www.viper-lab.com/).

## Install

### Composer

We recommend this command for linux based web servers.

```bash
composer require viper/lab
```

Recommended that you install it globally for development environments.

```bash
composer global require viper/lab
```

### Personal Private Access Token

Obtain your personal private access token from [ViperLab](https://www.viper-lab.com/).

## Usage

There are three types of environments we support currently.

| Type    | Path |
|---------|------|
| Docker  | `docker/.env` |
| Vagrant | `virtual/config.yml` |
| Viper   | `.env` |

### Docker

**Find by ID**

```bash
viper-lab docker --private-token=<TOKEN> --id=<ID>
```

**Find by Title**

```bash
viper-lab docker --private-token=<TOKEN> --title=<TITLE>
```

### Vagrant

```bash
viper-lab vagrant --private-token=<TOKEN> --title=<TITLE>
```

### Viper

```bash
viper-lab viper --private-token=<TOKEN> --title=<TITLE>
```
