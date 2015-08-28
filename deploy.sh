#! /bin/bash

FILE="ProfildienstImport.phar"

if [ -f $FILE ];
then
   scp $FILE krausz@esx-118.gbv.de:/home/krausz/Profildienst/import
else
   echo "File $FILE does not exists"
fi

