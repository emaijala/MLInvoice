MLInvoice
=========

In English
----------

MLInvoice is a free web-based invoicing system primarily for Finnish companies. It includes
support for creating and emailing PDFs and Finvoice electronic invoices. The code is written
and commented in English. The user interface is available in both Finnish and English, and
invoices can also be created in both languages independently of the UI language. Different
locale settings can be defined in the language files. It is also possible to add new
translations without having to touch the code.

Main Features:

- Client Registry
- Product Registry
- PDF Invoices
- Invoice archiving
- Reporting
- Email sending
- Finvoice support

See the MLInvoice home page at http://www.labs.fi/mlinvoice.eng.php for more information,
installation instructions and the change log.

N.B. If you install MLInvoice from git (not using one of the releases), be sure to
run "composer install" after downloading or cloning the repository to install
external dependencies. This is also the case for the release branch in GitHub.
Visit https://getcomposer.org/ if you don't already have composer installed.

Release packages at https://www.labs.fi/mlinvoice_installation.eng.php include the
dependencies.


Running with Docker
-------------------

Running MLInvoice in a Docker container is now also supported. MLInvoice is available
at Docker Hub: https://hub.docker.com/r/emaijala/mlinvoice/

For development purposes, Docker and Docker-compose are required. When they are
available, it's enough to download MLInvoice, extract it into a directory, enter the
directory in a terminal and execute the command `docker-compose up`. When the
containers have been created and have started, MLInvoice will be available at
http://localhost:8000/. Note that the database is created in the db_data
subdirectory, so make sure not to delete it unless you want to start from scratch.

Useful docker commands:

`docker-compose up` - Start the containers in foreground (Ctrl-C to stop)

`docker-compose up -d` - Start the containers in background (use `docker-compose stop` to stop them)

`docker-compose up --build` - Start the containers after rebuilding them

`docker-compose down` - Stop the containers

`docker run -it mlinvoice_mlinvoice /bin/bash` - Open a terminal inside the container


Running with Vagrant
--------------------

There is a separate MLInvoice-Vagrant project that allows running MLInvoice under
Vagrant. See https://github.com/emaijala/MLInvoice-Vagrant for more information.

Suomeksi
--------

MLInvoice on ilmainen web-pohjainen laskutusjärjestelmä erityisesti suomalaisille yrityksille.
Pääominaisuuksia ovat:

- Asiakasrekisteri
- Tuoterekisteri
- PDF-laskut
- Laskujen arkistointi
- Raportointi
- Sähköpostilähetys
- Finvoice-laskutus

Lisätietoja, asennus- ja päivitysohjeet sekä tiedot muutoksista uusissa versiossa löytyvät
MLInvoicen kotisivulta http://www.labs.fi/mlinvoice.php

HUOM! Jos asennat MLInvoicen suoraan git:stä, suorita "composer install" latauksen
tai kloonauksen jälkeen asentaaksesi ulkoiset riippuvuudet. Tämä koskee myös
release-haaraa GitHubissa. Jos sinulla ei vielä ole
composeria asennettuna, käy osoitteessa https://getcomposer.org/.

Julkaistut versiot osoitteessa https://www.labs.fi/mlinvoice_installation.php
sisältävät ulkoiset riippuvuudet.

MailGun support
sudo -u www-data composer require mailgun/mailgun-php php-http/guzzle6-adapter  guzzlehttp/psr7
