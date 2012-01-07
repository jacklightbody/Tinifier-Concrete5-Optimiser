<?php 
/*

	Tinifier
	---------

	@file 		tiny.php
	@date 		2011-05-27 22:20:36 -0400 (Wed, 1 June 2011)
	@update 	2011-9-14
	@author 	Jack Lightbody <jack.lightbody@gmail.com>
	Copyright   (c) 2011 Jack Lightbody <12345j.co.cc>
	@license 	Mit Open Source
	@github     https://github.com/12345j/Tinifier-Concrete5-Optimiser
	@version    1.5
*/
defined( 'C5_EXECUTE' ) or die( "Access Denied." );

class TinyHelper {
		private $jscompress=1;
		private $csscompress=1;
		public function tinify( $content ){
			$var=1;
			$file=loader::helper('file');
			$page = Page::getCurrentPage();
			$jsFileMerge = DIRNAME_JAVASCRIPT."/merge-".$page->getCollectionID()."-".$page->getVersion().".js";
			$cssFileMerge = DIRNAME_CSS."/merge-".$page->getCollectionID()."-".$page->getVersion().".css";
			if(file_exists($cssFileMerge)&&$this->csscompress==1){
				$var=0;
				$diff=$page->getCollectionID()-1;
				$cssFileMergePrev = DIRNAME_CSS."/merge-".$diff."-".$page->getVersion().".css";
				unlink($cssFileMerge);
			}
			if(file_exists($jsFileMerge)&&$this->jscompress==1){
				$var=0;
				$diff=$page->getCollectionID()-1;
				$jsFileMergePrev = DIRNAME_JAVASCRIPT."/merge-".$diff."-".$page->getVersion().".js";
				// check if the page already has stuff, if so we do it really fast.
				unlink($jsFileMergePrev);
			}
			/** Start Execute new page **/
			if($var==1){
				$jsCombine=array();
				$cssCombine=array();
				$unknownCss=array();
				$unknownJs=array();
				// Get all the javascript links to files and put their content in the merge js file			
				if ( preg_match_all( '#<\s*script\s*(type="text/javascript"\s*)?src=.+<\s*/script\s*>#smUi',$content,$jsLinks )) {
					if($this->jscompress==1){
						foreach ( $jsLinks[0] as $jsLink ) {
							if(preg_match('/<script type="text\/javascript" src="(.*)"><\/script>/', $jsLink )){
	         					$jsItem= preg_replace('/<script type="text\/javascript" src="(.*)"><\/script>/', '$1', $jsLink);// get whats in href attr  
	         					array_push($jsCombine, $jsItem);
	         					$content=str_replace($jsLink, '', $content);
	         				}elseif(preg_match('/<script.*class="nocombine".*<\/script>/', $jsLink )){
	         				}else{
	         					array_push($unknownJs, $jsLink);
	         					$content=str_replace($jsLink, '', $content);
	         				}
						}	
						foreach ($jsCombine as $js){
							$external = 'http://';
							$externalFile = strpos($js, $external);
							if($externalFile === false){
								$jsFile=BASE_URL.$js;
							}else{
								$jsFile=$js;
							}
					 		$jsFileContents=$file->getContents($jsFile);
							/*Compressing the js takes way too long so we just insert the uncompressed stuff. TODO: Speed it up- if its a not new version then don't compress it again. Do this with css too */
							//Loader::library( '3rdparty/jsmin' );
							//$jsCompress=JSMin::minify( $jsFileContents );	
							file_put_contents($jsFileMerge, $jsFileContents, FILE_APPEND);
						}	
					}else{
						foreach ( $jsLinks[0] as $jsLink ) {
							$content=str_replace($jsLink, '', $content);
							$content=str_replace('</body>', $jsLink.'</body>', $content);
						}	
					}
				}
				// get all the css links and add to merge
				if ( preg_match_all( '#<\s*link\s*rel="?stylesheet"?.+>#smUi',$content,$cssLinks )) {
					if($this->csscompress==1){
						foreach ($cssLinks[0] as $cssLink ) {
							if(preg_match('/<link rel="stylesheet" type="text\/css" href="(.*)" \/>/', $cssLink )){
	         						$cssItem= preg_replace('/<link rel="stylesheet" type="text\/css" href="(.*)" \/>/', '$1', $cssLink);// get whats in href attr  
	         						array_push($cssCombine, $cssItem);
	         					}else{
	         						array_push($unknownCss, $cssLink);
	         					}
	         					$content=str_replace($cssLink, '', $content);
						}	
						foreach($cssCombine as $css){				
							$cssFile=BASE_URL.$css;
					 		$cssFileContents=$file->getContents($cssFile);
					 		//$cssFileContent=preg_replace("#\url((.*)\)#is", '('.$css.'$1'.')', $cssFileContents);
					 		$cssCompress=cssCompress($cssFileContents);
							file_put_contents($cssFileMerge, $cssCompress, FILE_APPEND);	
						}
					}else{
						foreach ( $cssLinks[0] as $cssLink ) {
							$content=str_replace($cssLink, '', $content);
							$content=str_replace('</head>', $cssLink.'</head>', $content);
						}	
					}
				}
				// get all the inline css and add to merge
				if ( preg_match( '#<\s*style.*>.+<\s*/style\s*\/?>#smUi',$content,$inlineCss )>0 ) {
					foreach ( $inlineCss as $Inlinecssitem ) {
						$Inlinecssitem1=preg_replace('#<\s*style.*>#smUi', "", $Inlinecssitem);
						$Inlinecssitem1=preg_replace('#<\s*/style\s*\/?>#smUi', "", $Inlinecssitem1);
						$cssCompress=cssCompress($Inlinecssitem1);
						file_put_contents($cssFileMerge, $cssCompress, FILE_APPEND);
						$content=str_replace($Inlinecssitem, '', $content);
					}	
				}
			}
			/** End Execute new page **/
			$content =  str_ireplace( '</body>','<script type="text/javascript" src="'.ASSETS_URL_WEB.'/'.DIRNAME_JAVASCRIPT.'/merge-'.$page->getCollectionID().'-'.$page->getVersion().'.js"></body>', $content );	// add the script link to the footer
			// get all the inline javascript and add it to the footer (we need this below the merge)
			if(preg_match_all( '#<\s*script\s*(type="text/javascript"\s*)?>(.+)<\s*/script\s*>#smUi',$content,$inlineJavascript )){
				foreach ($inlineJavascript[0] as $inlineItem ) {
					$content=str_replace($inlineItem, '', $content);
					$content=str_replace('</body>', $inlineItem.'</body>', $content);
				}	
			}

				foreach($unknownJs as $jsU){
					$content=str_ireplace('</body>', $jsU.'</body>', $content);	// add the js link to the end					
				}
				foreach($unknownCss as $cssU){
					$content=str_ireplace( '</head>',$cssU.'</head>', $content );	// add the stylesheet link to the head					
				}
				$content =  str_ireplace( '</head>','<link rel="stylesheet" type="text/css" href="'.ASSETS_URL_WEB.'/'.DIRNAME_CSS.'/merge-'.$page->getCollectionID().'-'.$page->getVersion().'.css" /><!--Compressed by Tinifier v1.5--></head>', $content );	// add the stylesheet link to the head
				$content = preg_replace('/(?:(?<=\>)|(?<=\/\)))(\s+)(?=\<\/?)/','',$content);//remove html whitespace
				return $content;	
			}
		}
		function cssCompress($string) {
			/* remove comments */
		    $string = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $string);
			/* remove tabs, spaces, new lines, etc. */        
		    $string = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $string);
			/* remove unnecessary spaces */        
		    $string = str_replace('{ ', '{', $string);
		    $string = str_replace(' }', '}', $string);
		    $string = str_replace('; ', ';', $string);
		    $string = str_replace(', ', ',', $string);
		    $string = str_replace(' {', '{', $string);
		    $string = str_replace('} ', '}', $string);
		    $string = str_replace(': ', ':', $string);
		    $string = str_replace(' ,', ',', $string);
		    $string = str_replace(' ;', ';', $string); 
			return $string;
		}