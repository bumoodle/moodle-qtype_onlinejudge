HDL Simulation question type for Moodle 2.1+

Allows the student to upload a HDL file (VHDL, Verilog, Xilinx Schematic, or QFSM diagram), which will be evaluated against a testbench.

Authored by Kyle Temkin, working for Binghamton University <http://www.binghamton.edu>

To install Moodle 2.1+ using git, execute the following commands in the root of your Moodle install:

    git clone git://github.com/ktemkin/moodle-qtype_vhdl.git question/type/vhdl
    echo '/question/type/vhdl' >> .git/info/exclude
    
Or, extract the following zip in your_moodle_root/question/type/:

    https://github.com/ktemkin/moodle-qtype_vhdl/zipball/master
