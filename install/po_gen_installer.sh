#!/bin/bash

# Wavelog PO-File Generator 
# COMPONENT: Installer
#
# HB9HIL <mail@hb9hil.org>, 2024

# This script is designed to help create consistent POT, PO, and MO files during Wavelog development.
# If you have added '__("english test strings")' within PHP files, you can run this script. It will automatically:
# 
#  - Scan all PHP files for gettext functions: __(), _ngettext(), _pgettext() 
#  - Recreate the POT file with the found strings
#  - Merge the POT file into the PO files of each language
#  - Compile MO files from the PO files
#
# It is important that this script is placed in the root directory of Wavelog.

##########################  IMPORTANT ###########################
#                                                               #
#  Do NOT include the compiled MO files in a Pull Request.      #
#  This can cause unsolvable merge conflicts.                   #
#  We create the MO files directly within GitHub.               #
#                                                               #
#################################################################

# Some variables. Usually you don't need to change them.

POT_FILE="install/includes/gettext/lang_src/installer.pot"
BUG_MAIL="translations@wavelog.org"

YEAR="$(date +"%Y")"

POT_TITLE_TEXT="WAVELOG PO FILE"
POT_COPYRIGHT_TEXT="Copyright (c) $YEAR Wavelog by DF2ET, DJ7NT, HB9HIL and LA8AJA."
POT_LICENCE_TEXT="This file is distributed under the MIT licence."
FOLDERS="install"

# Find all PHP files and create a list in a temporary file 
find $FOLDERS -name "*.php" > PHPFILESLIST

# Run the xgettext command with various options. Do not change these options to keep the POT/PO files consistent in Wavelog
xgettext    -F \
            -o $POT_FILE \
            --from-code=UTF-8 \
            --keyword=__ \
            --keyword=_ngettext:1,2 \
            --keyword=_pgettext:1c,2 \
            -L PHP \
            --files-from=PHPFILESLIST \
            --msgid-bugs-address=$BUG_MAIL

# After the xgettext command, we don't need the temporary file anymore
rm PHPFILESLIST

# Let's edit the header of the POT file
sed -i "1s/.*/# $POT_TITLE_TEXT/" "$POT_FILE"
sed -i "2s/.*/# $POT_COPYRIGHT_TEXT/" $POT_FILE
sed -i "3s/.*/# $POT_LICENCE_TEXT/" $POT_FILE
sed -i '4d' $POT_FILE
sed -i '8d' $POT_FILE

# Extract the first three lines of the POT file to a temporary file
head -n 3 "$POT_FILE" > POT_HEADER

# Now we can merge the POT file (PO template) into each found PO file
for po in $(find $FOLDERS -name "*.po"); do
    msgmerge --update -vv --backup=none --no-fuzzy-matching "$po" $POT_FILE;
    # Replace the first three lines of the PO file with the POT file header
    sed -i '1,3d' "$po"
    cat POT_HEADER "$po" > temp.po && mv temp.po "$po"
    # Remove fuzzy markers from PO file
    sed -i '/^#, fuzzy$/d' "$po"
done

# Clean up the temporary POT_HEADER file
rm POT_HEADER

# The last action is to create a MO file for each found PO file
for po in $(find $FOLDERS -name "*.po"); do
    mo="${po%.po}.mo";
    msgfmt -vv -o "$mo" "$po";
done

# END