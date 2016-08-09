#!/bin/bash

if [ -z "$1" ]; then
   echo "Please provide the name of the database file to update"
else
   echo "Modifying database file "$1""

   # Add notes column to the income table
   if [ "$(sqlite3 $1 '.schema income' | grep -c notes)" -gt 0 ]; then
      echo "Notes already in the income table"
   else
      echo "Adding notes column to the income table"
      sqlite3 $1 'ALTER TABLE income ADD COLUMN notes VARCHAR(1000)'
      sqlite3 $1 '.schema income'
   fi

   # Add tax_deduct column to the transactions table
   if [ "$(sqlite3 $1 '.schema transactions' | grep -c tax_deduct)" -gt 0 ]; then
      echo "Tax deduct column already in the transactions table"
   else
      echo "Adding tax_decuct column to the transaction table"
      sqlite3 $1 'ALTER TABLE transactions ADD COLUMN tax_deduct INTEGER NOT NULL DEFAULT 0'
      sqlite3 $1 '.schema transactions'
   fi
fi
