#!/bin/bash

shopt -s extglob
cd /var/NXBoost/data
sudo rm -rf !(nxboost.conf)
shopt -u extglob
