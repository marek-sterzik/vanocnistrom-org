# www.vanocnistrom.org

Toto je kód webové aplikace, která běží na webu [www.vanocnistrom.org](https://www.vanocnistrom.org). Aplikace umožňuje zdobit virtuální vánoční stromek skrze dobře definované API.
Aplikaci lze používat ke vzdělávání i k zábavě.

## Vývojové prostředí

Vývojové prostředí je postaveno na platformě [spsostrov-php-runtime](https://github.com/marek-sterzik/spsostrov-php-runtime), kde jsou popsány podrobnosti použití. Platforma předpokládá
fungující instalaci dockeru a prostředí založené na Linuxu. Vývoj lze proto realizovat buď přímo v rámci nějaké linuxové distribuce, nebo také například na Windows v rámci subsystému
WSL (Windows subsystem for Linux).

Podrobnosti o použití vývojového prostředí najdete na [stránkách platformy spsostrov-php-runtime](https://github.com/marek-sterzik/spsostrov-php-runtime).

## Rychlý start

Pro rychlý start systému spusťte příkaz:

```
bin/docker start
```

**Aplikaci přitom spouštíme jako obyčejný uživatel, nikdy jako root!**

Aplikace se potom rozběhne na portu, který jste zadali v konfigurační části. Pokud jste ponechali základní port 80, budete mít aplikaci
k dispozici na adrese:

```
http://localhost
```

Pokud jste zadali jiné číslo portu, např. `8080`, bude aplikace dostupná na adrese:

```
http://localhost:8080
```
