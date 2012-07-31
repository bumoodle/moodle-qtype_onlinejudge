<?php
/**
 *
 * HDL Simulation...
 * @author ktemkin
 *
 * CURRENTLY ONLY WORKS ON A STANDARD LINUX ENVIRONMENT
 * (This can probably be ported to windows with some small amount of work,
 *  if ever anyone is interested.)
 *
 *  REQUIRES ISE TO BE INSTALLED,
 *  REQUIRES A SUPPORTING SHELL SCRIPT(!)
 */


class SimulationException extends Exception {}
class InvalidFiletypeException extends SimulationException {}

/**
 *
 * Core HDL Simulation class.
 *
 * ***START WARNING***
 *
 * I HAVE NOT DONE ANY VULERNABILITY ANALSYS ON THE XILINX ISIM APPLICATION, and it
 * probably wasn't intended to run code from users over the internet. There may be
 * inherent, latent security flaws.
 *
 * ***END WARNING***
 *
 * TODO: For increased security, run the ISIM executable as a sandboxed user.
 *
 * @author ktemkin
 *
 */
class HDLSimulation
{
    const KEEP_FILES = false;

    /**
     * Stores the temporary working "sandbox" directory for the simulation.
     */
    protected $work_dir;

    /**
     * Stores an array of allowed file extensions, which limit the user design, but not the testbench.
     */
    protected $allowed_exts;

    /**
     * Stores a string describing the last error to occur.
     */
    protected $last_error;

    /**
     * Stores the reference design (grading testbench) as an array of relevant files.
     */
    protected $reference;

    /**
     * Stores the user design (unit under test) as an array of relevant files.
     */
    protected $userdesign;

    /**
     * Stores a Moodle filesystem object, which allows us to interact with the simulation files.
     * TODO: abstract to a subclass, so HDLSimluation doesn't depend on moodle
     */
    protected $fs;

    /**
     * The raw output of the ISIM binary after the simulation has been run.
     */
    protected $raw_output;

    
    /**
     * The path to the script (or binary) on the server which runs the actual simulation.
     */
    const RUNTEST_SCRIPT = '/srv/autolab/runtest.sh';
    //const RUNTEST_SCRIPT = '/home/ktemkin/.bin/runtest.sh';

    /**
     * Maximum simulation runtime, in seconds.
     */
    const RUNTIME_MAX = 60;


    /**
     * Moodle database table which contains cached executions.
     */
    const CACHE_TABLE = 'question_vhdl_cache';

    /**
     * Message for a Xilinx error.
     */
    const XILINX_ERROR = 'The Xilinx tool reported an error when we tried to parse your design:';

    /**
     * Creates a new HDL Simulation object, from a Grading Testbench and User Design.
     *
     * @param int   $reference      The reference design (Grading Testbench) which will grade the User Design.
     * @param int   $user           The user design (unit under test), which is graded by the reference design.
     * @param array $allowed_types  An array of allowed file extensions; any uploaded user (not instructor) files that do not meet this extension will be discarded.
     * @param bool  $run_prep       True iff second-level initialization should be performed, which prepares the simulation for execution.
     */
    public function __construct(array $reference, array $user_design, $allowed_types=array('sch', 'vhdl', 'v', 'fsm'), $run_prep=true)
    {
            
        //create a local copy of the Moodle file storage class
        $this->fs = get_file_storage();
            
        //store a list of acceptable file extensions
        $this->allowed_exts = $allowed_types;
            
        //get references to the Reference (grading) Testbench and User Design
        //$this->reference = $this->get_files_by_itemid($reference);
        //$this->userdesign = $this->get_files_by_itemid($user);
        $this->reference = $reference;
        $this->userdesign = $user_design;
            
        //store a default 'catch all' error
        $this->last_error = get_string('erroroccurred', 'qtype_vhdl');

        //initialize marks to false
        $this->marks = false;

        //if the user isn't going to run prep seperately
        if($run_prep && !$this->get_cached())
            $this->prep_simulation();

    }


    /**
     * Returns a fraction iff the given testbench/design combination has been run before; to save CPU time.
     * 
     * @param stored_file $reference    The testbench design.
     * @param stored_file $user_design  The user's design file.
     * @return void
     */
    protected function get_cached()
    {
        global $DB;

        //merge the hashes from the testbench and user design; so we'll re-run the testbench if either of the two changes
        $hash = $this->get_hash();

        //attempt to read a cached response from the database, if it exists
        //$cached_runs = $DB->get_record(self::CACHE_TABLE, array('fileshash' => $DB->sql_compare_text($hash)));
        $cached_runs = $DB->get_record_select(self::CACHE_TABLE, 'fileshash = \''.$hash.'\'');

        //TODO: possibly rewrite?
        return $cached_runs;

    }

    public function get_hash()
    {
        $hash = '';

        //concat the content hash of each file in the reference design
        foreach($this->reference as $file)
            $hash .= $file->get_contenthash();

        //and concat the content hash of each file in the user design
        foreach($this->userdesign as $file)
            $hash .= $file->get_contenthash(); 

        //and return the merged hash
        return $hash;
    }

    /**
     * Returns an array of all files corresponding to the given ItemID.
     *
     * TODO: Find a more idomatic way to do this.
     *       (Which would be a lot easier if Moodle's idioms were documented.)
     *
     * @param int $itemid   The itemid of the files uploaded.
     * @return array  An array of all stored_files corresponding to the given ItemID. May be empty.
     */
    protected function get_files_by_itemid($itemid)
    {
        //FIXME use get_file instead


        global $DB;

        $files = array();
         
        //get all files with the given Item ID.
        $file_records = $DB->get_records('files', array('itemid' => $itemid));
         
        //for each file with a matching item ID.
        foreach($file_records as $record)
        {
            //ignore dotfiles
            if($record->filename[0] == '.')
                continue;

            //add the file to our array
            $files[] = $this->fs->get_file_instance($record);
        }
         
        //return all relevant files
        return $files;
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
        $this->work_dir = exec('mktemp -d');
    }

    /**
     * Delete the working directory and all of its contents.
     */
    protected function delete_work_dir()
    {
        //delete the temporary "sandbox" directory
        self::recursiveDelete($this->work_dir);
            
        //and clear the work_dir variable;
        $this->work_dir = '';
    }

    static function filecount(array $files)
    {
        $count = 0;

        //count only non-directories
        foreach($files as $file)
            if(!$file->is_directory())
                ++$count;


        //return the coutn
        return $count;
    }

    static function first_file(array $files)
    {
        foreach($files as $file)
            if(!$file->is_directory())
                return $file;
    }

    /**
     * Create working copies of the user and reference designs in the working directory.
     *
     * @throws InvalidFiletypeException If the user tried to upload a filetype that wasn't allowed.
     */
    protected function create_working_copies()
    {
        //if there exists only one schematic file, copy it to toplevel.sch, for compatibility
        if(self::filecount($this->userdesign) === 1 && self::file_extension(self::first_file($this->userdesign)->get_filename()) == "sch")
        {
            self::first_file($this->userdesign)->copy_content_to($this->work_dir.'/toplevel.sch');
        }
        else if(self::filecount($this->userdesign) === 1 && self::file_extension(self::first_file($this->userdesign)->get_filename()) == "fsm")
        {
            self::first_file($this->userdesign)->copy_content_to($this->work_dir.'/design.fsm');
        }
        else
        {
            //create the user design _first_
            foreach($this->userdesign as $file)
            {
                //skip directories and files with empty names
                if($file->is_directory() || trim($file->get_filename()) == '')
                    continue;

                //if the file isn't of an allowed type, ignore it
                if(!in_array(self::file_extension($file->get_filename()), $this->allowed_exts))
                {
                    //and throw an exception
                    //FIXME: get_string
                    $this->last_error = 'At least one of your files was ignored, as the filetype was not allowed by your instructor. ('.$file->get_filename().')';
                        throw new InvalidFiletypeException($this->last_error);
                }

                //copy the file to the working dir
                $file->copy_content_to($this->work_dir.'/'.$file->get_filename());
            }
                
        }
            
        //create the reference design _second_
        //it's important to create this second, so the user can't
        //overwrite any reference design files (and dishonestly get a higher grade)
        foreach($this->reference as $file)
        {
            //skip directories and files with empty names
            if($file->is_directory() || trim($file->get_filename()) == '')
                continue;

            //copy the file to the working dir
            $file->copy_content_to($this->work_dir.'/'.$file->get_filename());
        }
    }

    public function prep_simulation()
    {
        //create the (temporary) working directory
        $this->create_work_dir();
            
        //create local copies of all files in the working directory
        $this->create_working_copies();

        //TODO: pass ownership to the sandbox
        //(or make the files world-readable?)
    }

    /**
     * Returns the file extension of a filename, which may not exist in the filesystem.
     */
    protected static function file_extension($filename)
    {
        return strtolower(substr(strrchr($filename, '.'), 1));
    }

    /**
     * Perform the actual simualtion.
     */
    public function run_simulation($force_rerun = false, $cache=true)
    {
        //if we're not trying to force a rerun
        if(!$force_rerun)
        {
            //see if there's a cached attempt
            $cached = $this->get_cached();

            //if there is, use it
            if($cached)
            {
                //if the run was valid, restore the marks
                if($cached->valid)
                    $this->marks = unserialize($cached->marks);

                //otherwise, restore the last error
                else
                    $this->last_error = $cached->marks;

                //and return
                return;
            }
        }   

        //attempt to limit the runtime of the application
        //(the server typically has its own value- we may or may not be allowed to overwrite it
        @set_time_limit(RUNTIME_MAX);

        //move to the temporary directory
        chdir($this->work_dir);

        //generate a random token string- this is used to prevent the student from inserting their own
        //"grading statements" into their VHDL
        $security_token = substr(base_convert(mt_rand(mt_getrandmax()/2, mt_getrandmax()), 10, 36), 0, 6);

        //perform the command
        exec(self::RUNTEST_SCRIPT." ".$security_token, $this->raw_output);

        //parse the ISIM out to get grading and feedback information
        $this->marks = $this->parse_isim_out($this->raw_output, $security_token);

        //if caching is allowed, cache the results of this execution
        if($cache)
            $this->update_cache();

    }

    /**
     * Update the execution cache with the most recent set of marks.
     */
    protected function update_cache()
    {
        global $DB;

        //get any existing cache records, if they exist
        $cache = $this->get_cached();

        //if we didn't have a cache object, create a new, empty one
        if(!$cache)
            $cache = new stdClass;

        //update the cache object
        $cache->fileshash = $this->get_hash();
        $cache->date = time();
        $cache->valid = is_array($this->marks);

        //if we have a valid cache entry, seralize the marks 
        if($cache->valid)
            $cache->marks = serialize($this->marks);

        //otherwise, use the last error
        else
            $cache->marks = $this->last_error;



        //if the cache row existed, update it in the cache table
        if(property_exists($cache, 'id'))
            $DB->update_record(self::CACHE_TABLE, $cache);

        //otherwise, create a new entry in the DB
        else
            $DB->insert_record(self::CACHE_TABLE, $cache);
    }

    /**
     * Returns an array of all merits/demerits in the following format:
     * [ <number of points _added_ (may be neg.)>, <reason for mark>]
     */
    public function get_marks()
    {
        return $this->marks;
    }

    /**
     * Returns a HTML string which dislpays a formatted description of all merits/demerits.
     *
     * @param numeric   $total_grade    The maximum grade awarded; marks will be scaled appropriately.
     * @param string    $disclaimer     A short message which prefixes the table; typically lets the user know they're being automatically graded.
     */
    public function get_marks_str($total_grade=100, $disclaimer='This question was automatically graded.')
    {
        $marks = $this->get_marks();

        if(!count($marks) || !is_array($marks))
        return '';
            
        //create a simple table of comments
        //TODO: extract style?
        $buf = '<table width="80%">';
        $buf .= '<tr><td colspan="2" style="font-family: Courier New, Courier, monospace">'.$disclaimer.'</td></tr>';
            
        //add each mark to the array
        foreach($marks as $mark)
        $buf .= '<tr><td style="font-family: Courier New, Courier, monospace">'.$mark[1].'</td><td style="font-family: Courier New, Courier, monospace">'.number_format(($mark[0]/100)*$total_grade, 2).'</td></tr>';

        //end the table
        $buf .= '</table>';
            
        //return the HTML string
        return $buf;
    }

    /**
     * Computes a numeric grade (out of 100 points) for the student, based on simulation results.
     */
    public function get_grade()
    {
        $grade = 100;

        if($this->marks === false || !is_array($this->marks))
            throw new SimulationException($this->last_error);
            
        //if the array is empty, there were no merits or demerits; assume 100%
        if(!count($this->marks))
            return 1;
            
        //otherwise, tally all marks and demerits
        foreach($this->marks as $mark)
            $grade += $mark[0];

        //return the grade, as a fraction (in hundreths)
        return max(0, $grade) / 100;
    }

    /**
     * Cleans up after the simulation.
     */
    public function cleanup()
    {
        if(!self::KEEP_FILES)
        {
                //delete all temporary files created
                $this->delete_work_dir();
        }
    }

    /**
     * Parses a single line of ISIM output.
     */
    protected static function parse_report($report)
    {
            
        //assertions wihtout severity, or with severity error
        $error = '/^at [0-9]+ [npm]s: Error: ([-+][0-9]+|#)\|(.*)\|([a-z0-9]+)$/';

        //assertions with severity warning
        //at 5 ps, Instance /testbench/ : Warning: -100|Does not match reference design.
        $warning = ';^at [0-9]+ [npm]s, Instance /[A-Za-z_]+/ : Warning: ([-+][0-9]+|#)\|(.*)\|([a-z0-9]+)$;';

        //reports and assertions with severity note
        //at 1 ps: Note: #|START (/testbench/).
        $note = '/^at [0-9]+ [npm]s: Note: ([-+][0-9]+|#)\|(.*)\|([a-z0-9]*) \(\/[A-Za-z_]+\/\).$/';
         
        //note that $matches always corresponds to the correct item due to Short-Circuit evaluation
        if(preg_match($note, $report, $matches) || preg_match($warning, $report, $matches) || preg_match($error, $report, $matches))
            return array($matches[1], $matches[2], $matches[3]);
        else
            return false;

    }
     
    /**
     * Parses the output of the ISIM simulator, extracting grades and comments.
     * TODO: better errors messages on bad simulation runs
     *
     * @param array(string) $out                The raw output array from the ISIM simulator.
     * @param string        $security_token     A security token randomly generated, which should match the simulator's output.
     * @param int           $maximum_iterations The maximum amount of parsing iterations which should be allowed. This prevents wasting parsing time on simulations with excessive error messages.
     *
     * @note A low value for $maximum_iterations is reccomended to reduce the likelihood that this CPU-bound process can be used for DOS.
     */
    protected function parse_isim_out($out, $security_token = 0, $maximum_iterations = 10000)
    {
        $start_mark = array('#', 'START', $security_token);
        $end_mark = array('#', 'END', $security_token);

        //create an array to store all grades and comments
        $marks = array();

        //skip to the start mark
        $maxiter = $maximum_iterations;
        while(!empty($out))
        {
            //if the Xilinx tool throws an error, stop parsing with an error message
            if(substr($out[0], 0, 9) == 'ERROR:HDL')
            {
                $this->last_error = get_string('toolerror', 'qtype_vhdl') . '<br /><br /><div style="font-family: monospace;">'. htmlentities($out[0]).'</font>';
                return false;
                //throw new SimulationException(get_string('toolerror', 'qtype_vhdl') . '<br /><br /><div style="font-family: monospace;">'. htmlentities($out[0]).'</font>');
            }

            //if the Xilinx tool can't find the user design, stop parsing with an error message
            if(strpos($out[0], 'remains a black-box since it has no binding entity.') !== false)
            {
                $this->last_error = get_string('notcompatible', 'qtype_vhdl');
                return false;
            }
                

            //stop discarding lines once we reach the start control statement
            if($x = self::parse_report(array_shift($out)) == $start_mark)
                break;

            //if we've exceeded the maximum amount of iterations,
            //return false
            if(!$maxiter--)
                return false;
        }

        //process all lines up to the end mark
        $maxiter = $maximum_iterations;
        $sim_ok = false;

        while(!empty($out))
        {

                   $parsed = self::parse_report(array_shift($out));

            //ignore lines which are not reports
            if(!$parsed)
                continue;

            //if we have an invalid security token, suspect a hacking attempt and
            //quit parsing where we are
            if($parsed[2]!=$security_token)
                break;

            //stop when we get to the end mark
            if($parsed == $end_mark)
            {
                //mark that the simulation was OK
                $sim_ok = true;

                //and stop parsing
                break;
            }
             
            //if we've exceeded the maximum amount of iterations,
            //return false
            if(!$maxiter--)
                return false;

            //add the marks to the array
            array_push($marks, $parsed);
             
        }

        //don't grade based on a bad simulation
        if(!$sim_ok)
            return false;

        return $marks;
    }
        
    /**
     *
     * Recursively delete a given directory.
     *
     */
    private static function recursiveDelete($str)
    {
        //If we have a file, delete it.
        if(is_file($str))
        {
            return @unlink($str);
        }
        //Otherwise, if we have a directory:
        elseif(is_dir($str))
        {
            //try and find all paths within the directory
            $scan = glob(rtrim($str,'/').'/*');

            //delete each path in the directory
            foreach($scan as $index=>$path)
            self::recursiveDelete($path);

            //and delete the given directory
            return @rmdir($str);
        }
    }

    /**
     * Returns the last error message.
     */
    public function last_error()
    {
        return $this->last_error;
    }

}
