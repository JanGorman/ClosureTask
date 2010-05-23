<?php

require_once 'phing/Task.php';

class ClosureTask extends Task
{

    protected static $COMPILED_SUFFIX = '-comp';

    protected static $WHITESPACE_ONLY = 'WHITESPACE_ONLY';
    protected static $SIMPLE_OPTIMIZATIONS = 'SIMPLE_OPTIMIZATIONS';
    protected static $ADVANCED_OPTIMIZATIONS = 'ADVANCED_OPTIMIZATIONS';
    
    protected static $CHARSET = 'utf-8';

    /**
     * Source Files
     * @var FileSet
     */
    protected $filesets = array();
    
    /**
     * The path to compiler.jar
     * @var string
     */
    protected $compilerPath = 'compiler.jar';
    
    /**
     * Compiled file suffix
     * @param string
     */
    protected $compiledSuffix = null;
    
    /**
     * The closure compilation level
     * @param string
     */
    protected $compilationLevel = null;
    
    /**
     * The compiled charset
     * @param string
     */
    protected $charset = null;
    
    /**
     * The output file name
     * @param string
     */
    protected $outputFile = null;
    
    /**
     * Merge files
     * @param boolean
     */
    protected $merge = false;
    
    /**
     * Target for closure files
     * @param string
     */
    protected $targetDir;
    
    public function init()
    {
        return true;
    }
    
    public function createFileSet()
    {
        $num = array_push( $this->filesets, new FileSet() );
        return $this->filesets[$num - 1];
    }
    
    public function main()
    {
        if ( !isset( $this->charset ) )
        {
            $this->charset = self::$CHARSET;
        }
        if ( !isset( $this->compilationLevel ) )
        {
            $this->compilationLevel = self::$WHITESPACE_ONLY;
        }
        if ( !isset( $this->targetDir ) )
        {
            $this->targetDir = '.';
        }
        
        foreach ( $this->filesets as $fileset )
        {
            try {
                $files = $fileset->getDirectoryScanner( $this->project )->getIncludedFiles();
                $fullPath = realpath( $fileset->getDir( $this->project ) );
                
                if ( $this->merge === true && isset( $this->outputFile ) )
                {
                    $mergeFiles = array();
                }
                
                // Compile individual files or collect them for merging afterwards
                foreach ( $files as $file )
                {
                    if ( isset( $mergeFiles ) )
                    {
                        $mergeFiles[] = $file;
                    }
                    else
                    {
                        $target = $this->targetDir . DIRECTORY_SEPARATOR .  str_replace( $fullPath, '',
                            str_replace( '.js', ( isset( $this->compiledSuffix ) ? $this->compiledSuffix : self::$COMPILED_SUFFIX ). '.js', $file ) );
                        
                        $this->makeTargetDirectory( $target );
                        $this->compile( $file, $target );
                    }
                }
                
                if ( isset( $mergeFiles ) )
                {
                    $this->makeTargetDirectory( $this->outputFile );
                    $this->compile( implode( ' --js ', $mergeFiles ), $this->outputFile );
                }
                
            } catch ( BuildException $e ) {
                throw $e;
            }
        }
    }
    
    /**
     * Check if the target file directory exists, create it if not
     * 
     * @param string $target
     * 
     * @return void
     */
    protected function makeTargetDirectory ( $target )
    {
        if ( file_exists( dirname( $target ) ) === false )
        {
            mkdir( dirname( $target ), 0700, true );
        }
    }
    
    /**
     * Do the actual compilation
     * 
     * @param string $input
     * @param string $target
     * 
     * @return void
     */
    protected function compile( $input, $target )
    {
        $cmd = escapeshellcmd( "java -jar $this->compilerPath --charset $this->charset --compilation_level $this->compilationLevel --js_output_file $target --js $input" );
        exec( $cmd );
    }
    
    public function setCompilerPath( $compilerPath )
    {
        $this->compilerPath = $compilerPath;
    }
    
    public function setTargetDir( $targetDir )
    {
        $this->targetDir = $targetDir;
    }
    
    public function setCompilationLevel( $compilationLevel )
    {
        if ( !in_array( $compilationLevel, array( self::$WHITESPACE_ONLY, self::$SIMPLE_OPTIMIZATIONS, self::$ADVANCED_OPTIMIZATIONS ) ) )
        {
            throw new Exception( "The compilation level '$compilationLevel' is not a valid option." );
        }
        $this->compilationLevel = $compilationLevel;
    }
    
    public function setCharset( $charset )
    {
        $this->charset = $charset;
    }
    
    public function setOutputFile( $outputFile )
    {
        $this->outputFile = $outputFile;
    }
    
    public function setMerge( $merge )
    {
        $this->merge = $merge;
    }
    
}