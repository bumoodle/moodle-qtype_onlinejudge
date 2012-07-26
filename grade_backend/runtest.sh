#!/bin/bash

export XILINX=/opt/Xilinx/13.1/ISE_DS/ISE/
export PLATFORM=lin64
export PATH=$PATH:${XILINX}/bin/${PLATFORM}
export LD_LIBRARY_PATH=${XILINX}/lib/${PLATFORM}
export FSM_TOOL=/srv/autolab/qfsm2hdl

#create the TCL script which runs the test
#this, incidentally
echo "put security_token $1" > sim.tcl
echo "run 100 us" >> sim.tcl
echo "quit" >> sim.tcl

#convert all present schematic files to VHDL
for i in *.sch
do
	if [ -f $i ]; then 
			sch2vhdl $i
	fi
done

#convert all present FSM files to VHDL
for i in *.fsm
do
    if [ -f $i ] 
    then
	    ${FSM_TOOL} $i > $i.vhd	
	
	    if [ $? -ne 0 ]; then
			
			cat $i.vhd
			exit $LAST_ERROR
	    fi
    fi
done

#parse all present VHDL files into a "virtual project"
vhpcomp *.vhd 2>&1

#and create a simulation executable (capable of running test scripts)
fuse testbench -o uut 2>&1

#and run the testbench
./uut -tclbatch sim.tcl 2>&1
