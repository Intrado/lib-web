#!/bin/bash

if [ -d build ]
then
	rm -rf build
fi
unzip build.zip -d build > /dev/null
cd build

unzip authserver-$1.zip -d authserver > /dev/null
unzip reportserver-$1.zip -d reportserver > /dev/null
unzip redialer-$1.zip -d redialer > /dev/null
unzip dm-$1.zip > /dev/null
unzip kona-$1.zip > /dev/null
unzip prompts-$1.zip > /dev/null
unzip tts-$1.zip > /dev/null

if [ -d /cygdrive/c/commsuite ]
then
	directory=/cygdrive/c/commsuite
else
	directory=/usr/commsuite
fi

for type in phone
do
        echo "checking to upgrade $type DM"
        if [ -d $directory/server/delmech-$type ]
        then
                echo "$type DM found"
                cp -Rf webapp/* $directory/server/delmech-$type/
        else
                echo "$type DM not found"
        fi
done

if [ ! -d $directory/server/ttsserver/ ]
then
		echo "Ttsserver directory not found, creating ttsserver directory."
		mkdir $directory/server/ttsserver
fi
echo "Upgrading ttsserver."
cp -Rf webapptts/* $directory/server/ttsserver/
echo "Done with ttsserver."


if [ ! -d $directory/www ]
then
		echo "WWW directory not found, creating www directory."
		mkdir $directory/www
fi
echo "Upgrading www."
cp -Rf kona/* $directory/www
chmod -R 550 $directory/www
chmod -R 777 $directory/www/tmp
chmod -R 777 $directory/www/dmapi/tmp
chown -R apache:apache $directory/www
echo "Done with www."


if [ ! -d $directory/cache/prompts/ ]
then
		echo "Prompts directory not found, creating prompts directory."
		mkdir $directory/cache/prompts
fi
echo "Upgrading prompts."
cp -Rf prompts/* $directory/cache/prompts/
echo "Done with prompts."

if [ ! -d $directory/server/redialer/ ]
then
        echo "Redialer directory not found, creating redialer directory."
        mkdir $directory/server/redialer
fi
cp -Rf redialer/* $directory/server/redialer/
echo "Done with redialer."


if [ ! -d $directory/server/authserver/ ]
then
        echo "Authserver directory not found, creating authserver directory."
		mkdir $directory/server/authserver
fi
cp -Rf authserver/* $directory/server/authserver/
echo "Done with authserver."

if [ ! -d $directory/server/reportserver/ ]
then
        echo "Reportserver directory not found, creating reportserver directory."
		mkdir $directory/server/reportserver
fi
cp -Rf reportserver/* $directory/server/reportserver/
echo "Done with reportserver."


echo "removing temp directories"
rm -Rf kona
rm -Rf prompts
rm -Rf redialer
rm -Rf webapp
rm -Rf webapptts
rm -Rf authserver
rm -Rf reportserver

cd $directory/www/build
php commsuite.php
