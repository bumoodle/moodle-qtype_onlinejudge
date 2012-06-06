<?php

require_once("$CFG->dirroot/question/type/vhdl/HDLSimulation.class.php");
require_once("$CFG->dirroot/question/type/vhdl/RemoteConnection.class.php");

/**
 *
 * Remote HDL Simulation class,
 * for running a HDL Simulation on a remote server.
 *
 *
 * @author ktemkin
 *
 */
class RemoteHDLSimulation extends HDLSimulation
{
    /**
     * A RemoteConnection object which interacts with the remote server.
     */
    protected $remote;

    /**
     * The full path to the script (or binary) on the remote server which runs the actual simulation.
     */
    const RUNTEST_SCRIPT = '/srv/autolab/runtest.sh';
    
    /**
     * Creates a new HDL Simulation object, from a Grading Testbench and User Design.
     *
     * @param int   $reference      The reference design (Grading Testbench) which will grade the User Design.
     * @param int   $user           The user design (unit under test), which is graded by the reference design.
     * @param array $allowed_types  An array of allowed file extensions; any uploaded user (not instructor) files that do not meet this extension will be discarded.
     * @param bool  $run_prep       True iff second-level initialization should be performed, which prepares the simulation for execution.
     */
    public function __construct($reference, $user, $allowed_types=array('sch', 'vhdl', 'v'), $run_prep=true)
    {
        //create a new connection to the Remote ISE Server
        $this->remote = new RemoteConnection();
        
        parent::__construct($reference, $user, $allowed_types=array('sch', 'vhdl', 'v'), $run_prep=true);
    }
        
    /**
     * Creates a temporary working directory for execution of the user's simulation.
     */
    protected function create_work_dir()
    {
        //if we have a working directory already, delete it before we create a new one
        if(!empty($this->work_dir))
            $this->delete_work_dir();
            
        //create a temporary directory
        $this->work_dir = $this->remote->execute('mktemp -d');
    }

    /**
     * Delete the working directory and all of its contents.
     */
    protected function delete_work_dir()
    {
        //this is _very dangerous_ if you're not running as a user with zero permissions!
        $this->remote->execute('rm -rf '.$this->work_dir);
    }

    /**
     * Create working copies of the user and reference designs in the working directory.
     *
     * @throws InvalidFiletypeException If the user tried to upload a filetype that wasn't allowed.
     */
    protected function create_working_copies()
    {
        
        //if there exists only one schematic file, copy it to toplevel.sch, for compatibility
        if(count($this->userdesign)==1 && self::file_extension($this->userdesign[0]->get_filename()) == "sch")
        {
            $this->remote->create_file($this->work_dir.'/toplevel.sch', $this->userdesign[0]->get_content());   
        }
        //oterhwise
        else
        {
            //create the user design _first_
            foreach($this->userdesign as $file)
            {

                //if the file isn't of an allowed type, ignore it
                if(!in_array(self::file_extension($file->get_filename()), $this->allowed_exts))
                {
                    //and throw an exception
                    $this->last_error = 'At least one of your files was ignored, as the filetype was not allowed by your instructor. ('.$file->get_filename().')';
                    throw new InvalidFiletypeException($this->last_error);
                }
                
                
                //copy the file to the remote working dir
                $this->remote->create_file($this->work_dir.'/'.$file->get_filename(), $file->get_content());        
            }
                
        }
            
        //create the reference design _second_
        //it's important to create this second, so the user can't
        //overwrite any reference design files (and dishonestly get a higher grade)
        foreach($this->reference as $file)
        {
            //copy the file to the remote working dir
            $this->remote->create_file($this->work_dir.'/'.$file->get_filename(), $file->get_content());    
        }
    }

    /**
     * Perform the actual simualtion.
     */
    public function run_simulation()
    {
        //attempt to limit the runtime of the application
        //(the server typically has its own value- we may or may not be allowed to overwrite it
        @set_time_limit(RUNTIME_MAX);

        //generate a random token string- this is used to prevent the student from inserting their own
        //"grading statements" into their VHDL
        $security_token = base_convert(mt_rand(0x1D39D3E06400000, 0x41C21CB8E0FFFFFF), 10, 36);

        //move to the working directory, and perform the command
        $this->remote->execute('cd '.$this->work_dir.'; '.self::RUNTEST_SCRIPT." ".$security_token, $this->raw_output);
        
        //parse the ISIM out to get grading and feedback information
        $this->marks = self::parse_isim_out($this->raw_output, $security_token);

    }




}
