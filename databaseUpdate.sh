#!/bin/bash

if [ -z "$1" ]; then
   echo "Please provide the name of the database file to update"
else
   echo "Adding notes column to the income table for the database file $1"
   sqlite3 $1 'ALTER TABLE income ADD COLUMN notes VARCHAR(1000)'
   sqlite3 $1 '.schema income'
fi
