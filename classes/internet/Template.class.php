<?php
/*
	* Last-Modification:	2008/11/29 17:00
	* Author:				Julien Vaslet (julien.vaslet@gmail.com)
	* Tested by:			Romain Guefveneu
	*/

/*
	* TODO:
	* 	- /!\ Avoid the use of php variables into the private evaluate function.
	*  - Remove blank spaces and rows around replaced variables. (Add a [\s]* at the begining and end of expreg ?)
	*  - Provide a debug option with multiple possibilities: variables, trace.
	*  - Provide a clean parameter in function show which allow the remove of useless blanks
	*  - Use of XML parser is it faster than expreg ?
	*  - Create a <php></php> area to allow PHP code.
	*/

class Block
{
	protected $name;
	protected $variables;
	protected $blocks;
	
	public function __construct( $name, $variables = array(), $blocks = array() )
	{
		$this->name = $name;
		$this->variables = array(); $this->addVariables( $variables );
		$this->blocks = $blocks;
	}
	
	
	public function addVariable( $name, $value )
	{
		$this->variables[ $name ] = ( $value === FALSE ) ? 0 : utf8_encode( $value );
	}
	
	public function addVariables( $array )
	{
		foreach( $array as $name => $value )
			$this->addVariable( $name, $value );
	}
	
	public function getVariable( $name )
	{
		return $this->variables[ $name ];
	}
	
	public function getVariables()
	{
		return $this->variables;
	}
	
	public function addBlock( $block )
	{
		$this->blocks[] = $block;
	}
	
	public function getBlocks()
	{
		return $this->blocks;
	}
	
	public function getName()
	{
		return $this->name;
	}
}

define( 'TEMPLATE_EXPREG_INCLUDE',   '/<[\s]*include[\s]+file[\s]*=[\s]*"([a-zA-Z0-9\_\-\.\/]+)"[\s]*\/[\s]*>/s' );
define( 'TEMPLATE_EXPREG_BLOCK',   '/<[\s]*block[\s]+name[\s]*=[\s]*"([a-zA-Z0-9\_\-\.]+)"[\s]*>(.*)<[\s]*\/[\s]*block[\s]*>/s' );
define( 'TEMPLATE_EXPREG_BEGINBLOCK',  '/<[\s]*block[\s]+name[\s]*=[\s]*"([a-zA-Z0-9\_\-\.]+)"[\s]*>/s' );
define( 'TEMPLATE_EXPREG_ENDBLOCK',  '/<[\s]*\/[\s]*block[\s]*>/s' );
define( 'TEMPLATE_EXPREG_RECURSIVE', '/<[\s]*recursive[\s]+block[\s]*=[\s]*"([a-zA-Z0-9\_\-\.]+)"[\s]*\/[\s]*>/' );
define( 'TEMPLATE_EXPREG_CONDITION',  '/<[\s]*if[\s]+exp[\s]*=[\s]*"([a-zA-Z0-9\_()=<>!%\/\{\}\'\-\.\s]+)"[\s]*>(.*)<[\s]*\/[\s]*if[\s]*>/s' );
define( 'TEMPLATE_EXPREG_IF',    '/<[\s]*(if)[\s]+exp[\s]*=[\s]*"([a-zA-Z0-9\_()=<>!%\/\{\}\'\-\.\s]+)"[\s]*>/s' );
define( 'TEMPLATE_EXPREG_ELSEIF',   '/<[\s]*(elseif)[\s]+exp[\s]*=[\s]*"([a-zA-Z0-9\_()=<>!%\/\{\}\'\-\.\s]+)"[\s]*\/[\s]*>/s' );
define( 'TEMPLATE_EXPREG_ELSE',   '/<[\s]*(else)[\s]*\/[\s]*>/s' );
define( 'TEMPLATE_EXPREG_ENDIF',   '/<[\s]*(\/[\s]*if)[\s]*>/s' );

class Template extends Block
{
	protected $path;

	public function __construct( $path )
	{
		parent::__construct( 'root' );
		$this->modules = array();
		$this->path = ( $path{ strlen( $path ) - 1 } == '/' ) ? substr( $path, 0, strlen( $path ) - 1 ) : $path;
	}
	
	public function show( $file )
	{
		if( file_exists( $this->path.'/'.$file ) )
		{
			$slashPos = strrpos( $file, '/' );
			$this->addVariable( 'TemplatePath', ( $slashPos > -1 ) ? $this->path.'/'.substr( $file, 0, $slashPos ) : $this->path );
				
			echo $this->loadFile( $this->path.'/'.$file );
		}
		else
		{
			// Debug purposes
			//die( 'File "'.$this->path.'/'.$file.'" not found' );
			header( 'HTTP/1.1 500 Internal Server Error' );
		}
	}
	
	public function loadFile( $file )
	{
		// Getting the file content
		ob_start();
		include( $file );
		$content = ob_get_contents();
		ob_end_clean();
		
		$content = $this->processBlocks( $content, $this->blocks );
		$content = $this->processConditions( $content );
		$content = $this->processVariables( $content );
		$content = $this->processIncludes( $content );
		
		return $content;
	}
	
	protected function processIncludes( $content )
	{
		// For all the include tags
		while( preg_match( TEMPLATE_EXPREG_INCLUDE, $content, $include ) )
		{
			// We load the specified file
			$content = preg_replace( '/'.str_replace( '/', '\\/', $include[0] ).'/', $this->loadFile( $this->path.'/'.$include[1] ), $content ); 
		}
		
		return $content;
	}
	
	protected function processVariables( $sContent, $bDeleteUnknow = false, $bTypedVariables = false )
	{
		// For all the known variables
		foreach( $this->variables as $key => $value )
		{
			// If we need typed variables
			if( $bTypedVariables )
			{
				if( is_string( $value ) )
					$value = "'$value'";
			}
			
			// We replace the name by the value
			$sContent = preg_replace( '/\{'.$key.'\}/', $value, $sContent );
		}
		
		if( $bDeleteUnknow )
		{
			$sContent = preg_replace( '/\{[a-zA-Z0-9\_\-\.]+\}/', ( $bTypedVariables ) ? 'false' : '', $sContent );
		}
		
		return $sContent;
	}
	
	protected function processBlocks( $content, $blocks )
	{
		while( preg_match( TEMPLATE_EXPREG_BLOCK, $content, $block, PREG_OFFSET_CAPTURE ) ) //while
		{
			preg_match_all( TEMPLATE_EXPREG_BEGINBLOCK, $block[0][0], $beginBlocks, PREG_OFFSET_CAPTURE );
			preg_match_all( TEMPLATE_EXPREG_ENDBLOCK, $block[0][0], $endBlocks, PREG_OFFSET_CAPTURE );
			
			/*
				* preg_match_all returns arrays like this : (n is the number of captured elements)
				* 
				* [ 0 ] [ n ] [ 0 ] : Full text
				* [ 0 ] [ n ] [ 1 ] : Offset
				* 
				* And these elements for open tags :
				* [ 1 ] [ n ] [ 0 ] : Block Name
				* [ 1 ] [ n ] [ 1 ] : Offset
				* 
				* It should be translated to this type of array :
				* 
				* Offset => array( (Boolean) Open tag or close tag, Full Text );
				* 
				* It will be sorted by offset with ksort() function.
				*/
			
			$aBlocks = array();
			
			for( $i = 0 ; $i < count($beginBlocks[0]) ; $i++ )
				$aBlocks[ $beginBlocks[0][$i][1] ] = array( true, $beginBlocks[0][$i][0] );
				
			for( $i = 0 ; $i < count($endBlocks[0]) ; $i++ )
				$aBlocks[ $endBlocks[0][$i][1] ] = array( false, $endBlocks[0][$i][0] );
			
			// We sort the array by keys whiches are offset
			ksort( $aBlocks );
			
			$numberOfImbricatedBlocks = 0;
			$startBlock = -1;
			$startOffset = -1;
			$endOffset = -1;
			$endBlock = -1;
			
			foreach( $aBlocks as $offset => $bInfo )
			{
				if( $bInfo[0] )
				{
					if( $numberOfImbricatedBlocks++ == 0 )
					{
						if( $startBlock < 0 )
							$startBlock = $offset;
						
						if( $startOffset < 0 )
							$startOffset = $offset + strlen( $bInfo[1] );
					}
				}
				else
				{
					if( --$numberOfImbricatedBlocks == 0 )
					{
						$endOffset = $offset;
						$endBlock = $offset + strlen( $bInfo[1] );
						break;
					}
				}
			}
			
			$blockContent = substr( $block[0][0], $startOffset, $endOffset - $startOffset );
			$newBlockContent = '';
			
			foreach( $blocks as $b )
			{
				if( $b->getName() == $block[1][0] )
				{
					$bContent = $blockContent; 
					
					foreach( $b->getVariables() as $name => $value )
					{
						$bContent = preg_replace( '/\{'.$b->getName().'.'.$name.'\}/', $value, $bContent );
					}
					
					// Replace <recursive /> tags
					$bContent = preg_replace( str_replace( '([a-zA-Z0-9\_\-\.]+)', $b->getName(), TEMPLATE_EXPREG_RECURSIVE ), '<block name="'.$b->getName().'">'.$blockContent.'</block>', $bContent );
					
					// Recursive process for the sub-blocks
					$newBlockContent .= $this->processBlocks( $bContent, $b->getBlocks() );
				}
			}
			
			$content = str_replace( substr( $block[0][0], $startBlock, $endBlock - $startBlock ), $newBlockContent, $content );
		}
		
		return $content;
	}
	
	protected function processConditions( $content )
	{
		// For all the found conditions
		while( preg_match( TEMPLATE_EXPREG_CONDITION, $content, $condition, PREG_OFFSET_CAPTURE ) )
		{
			// We are looking for every other conditions in the captured block
			preg_match_all( TEMPLATE_EXPREG_IF, $condition[0][0], $ifConditions, PREG_OFFSET_CAPTURE );
			preg_match_all( TEMPLATE_EXPREG_ELSEIF, $condition[0][0], $elseifConditions, PREG_OFFSET_CAPTURE );
			preg_match_all( TEMPLATE_EXPREG_ELSE, $condition[0][0], $elseConditions, PREG_OFFSET_CAPTURE );
			preg_match_all( TEMPLATE_EXPREG_ENDIF, $condition[0][0], $endifConditions, PREG_OFFSET_CAPTURE );
			
			/*
				* preg_match_all returns arrays like this : (n is the number of captured elements)
				* 
				* [ 0 ] [ n ] [ 0 ] : Full text
				* [ 0 ] [ n ] [ 1 ] : Offset
				* [ 1 ] [ n ] [ 0 ] : Condition type
				* [ 1 ] [ n ] [ 1 ] : Offset
				* [ 2 ] [ n ] [ 0 ] : Expression (in the else case, there is no expression)
				* [ 2 ] [ n ] [ 1 ] : Offset
				* 
				* It should be translated to this type of array :
				* 
				* Key     : Offset
				* [ n ] [ 0 ] : Full Text
				* [ n ] [ 1 ] : Condition type
				* [ n ] [ 2 ] : Expression
				* 
				* It will be sorted by offset with ksort() function.
				*/
			
			$conditions = array();
			
			for( $i = 0 ; $i < count($ifConditions[0]) ; $i++ )
				$conditions[ $ifConditions[0][$i][1] ] = array( $ifConditions[0][$i][0], $ifConditions[1][$i][0], $ifConditions[2][$i][0] );
			
			for( $i = 0 ; $i < count($elseifConditions[0]) ; $i++ )
				$conditions[ $elseifConditions[0][$i][1] ] = array( $elseifConditions[0][$i][0], $elseifConditions[1][$i][0], $elseifConditions[2][$i][0] );
				
			for( $i = 0 ; $i < count($elseConditions[0]) ; $i++ )
				$conditions[ $elseConditions[0][$i][1] ] = array( $elseConditions[0][$i][0], $elseConditions[1][$i][0], '1' );
				
			for( $i = 0 ; $i < count($endifConditions[0]) ; $i++ )
				$conditions[ $endifConditions[0][$i][1] ] = array( $endifConditions[0][$i][0], $endifConditions[1][$i][0], '1' );
				
			// We sort the array by keys whiches are offset
			ksort( $conditions );
			
			$numberOfImbricatedIf = 0;
			$beginOffset = -1;
			$endOffset = -1;
			$endBlockCondition = -1;
			
			foreach( $conditions as $offset => $cdn )
			{
				if( $cdn[1] == 'if' )
				{
					// If we are in the first level of condition
					if( ++$numberOfImbricatedIf == 1 && $beginOffset < 0 )
					{
						if( $this->evaluate( $cdn[2] ) )
						{
							$beginOffset = $offset + strlen( $cdn[0] );
						}
					}
				}
				else if( $cdn[1] == 'elseif' )
				{
					// If we are in the first level of condition
					if( $numberOfImbricatedIf == 1 )
					{
						if( $beginOffset < 0 )
						{
							if( $this->evaluate( $cdn[2] ) )
							{
								$beginOffset = $offset + strlen( $cdn[0] );
							}
						}
						else if( $endOffset < 0 )
						{
							$endOffset = $offset;
						}
					}
				}
				else if( $cdn[1] == 'else' )
				{
					// If we are in the first level of condition
					if( $numberOfImbricatedIf == 1 )
					{
						if( $beginOffset < 0 )
						{
							$beginOffset = $offset + strlen( $cdn[0] );
						}
						else if( $endOffset < 0 )
						{
							$endOffset = $offset;
						}
					}
				}
				else
				{
					// We are in /if case
					if( $numberOfImbricatedIf-- == 1 )
					{
						$endBlockCondition = $offset + strlen( $cdn[0] );
						
						if( $beginOffset > 0 && $endOffset < 0 )
							$endOffset = $offset;

						break;
					}
				}
			}
			
			// We replace the whole condition block by the selected subblock
			$content = str_replace( substr( $condition[0][0], 0, $endBlockCondition ), substr( $condition[0][0], $beginOffset, $endOffset - $beginOffset ), $content );
		}
		
		return $content;
	}
	
	private function evaluate( $sCondition )
	{
		$bReturn = false;
		
		try
		{
			// Replacement of variables
			$bReturn = eval( 'return ('.$this->processVariables( $sCondition, true, true ).');' ); 
		}
		catch( Exception $e )
		{
			$bReturn = false;
		}
		
		return $bReturn;
	}
}

?>
