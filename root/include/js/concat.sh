#!/bin/sh

OUTPUT_FILE="jquery-env.min.js"

cat jquery/jquery-3.1.0.min.js > $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE

cat jquery/moment.min.js >> $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE

cat jquery/jquery-daterangepicker.min.js >> $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE

cat jquery/jquery.jplayer.min.js >> $OUTPUT_FILE
echo "\n" >> $OUTPUT_FILE


