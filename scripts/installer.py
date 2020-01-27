#!/usr/bin/env python
# This Python file uses the following encoding: utf-8
# -*- coding: utf-8 -*-
import os
import time
import datetime
import subprocess

BRANCH_NAME = "{branch_name}"
FORK_URL    = "{fork_url}"
TIME_ZONE   = "{time_zone}"
CONTINUE_SH = '''#!/usr/bin/bash

cd /data/openpilot
exec ./launch_openpilot.sh
'''

def log(*info):
    print(*info)

def system(cmd):
  try:
    subprocess.check_output(cmd, stderr=subprocess.STDOUT, shell=True)
  except subprocess.CalledProcessError as e:
    log("ERROR: command running failed", e.cmd, e.output[-1024:], e.returncode)

def time_valid():
    return datetime.datetime.now().year > 2019

if __name__ == "__main__":
    log("Check time valid")
    while (not time_valid()):
        log("Wait time valid ...")
        time.sleep(.1)

    log("Start install openpilot")
    # clean tmp dir
    cmd = "rm -rf /tmp/openpilot"
    system(cmd)
    # clone tmp openpilot
    cmd = "git -C /tmp clone %s -b %s --depth=1 openpilot" % (FORK_URL, BRANCH_NAME)
    system(cmd)


    # Cleanup old folder in /data
    cmd = "rm -rf /data/openpilot"
    system(cmd)

    # this won't move if /data/openpilot exists
    cmd = "mv /tmp/openpilot /data"
    system(cmd)


    log("Start install continue.sh")
    try:
        continue_tmp  = "/data/data/com.termux/files/continue.sh.new"
        continue_dst = "/data/data/com.termux/files/continue.sh"
        with open(continue_tmp, "w") as f:
            f.write(CONTINUE_SH)
        cmd = "chmod +x %s" % continue_tmp
        system(cmd)
        os.rename(continue_tmp, continue_dst)
    except:
        log("ERROR: continue.sh installing error")

    log("Set timezone")
    cmd = "setprop  persist.sys.timezone %s" % TIME_ZONE
    system(cmd)

    log("Installing Completed")


