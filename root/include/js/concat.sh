#!/bin/sh

OUTPUT_FILE="jquery_components.js"

cat jquery/jquery.tools.min.js > $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE

cat jquery/jquery-ui-1.10.1.custom.min.js >> $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE

cat jquery/jquery-hashchange.js >> $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE

cat jquery/jquery.jplayer.min.js >> $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE





