<?php
/*!
Class: Barcode128
Version : 1.0
Released: 05-30-2002
Author: GuinuX <guinux@cosmoplazza.com>


**** usage:
See the example scripts or the PDFbarcode128 class available at : http://oakley.mirrors.phpclasses.org/browse.html/package/592/

License: The GNU General Public License (GPL)
http://www.opensource.org/licenses/gpl-license.html

For any suggestions or bug report please contact me : guinux@cosmoplazza.com

!*/

	class barcode128 {
		var $_b_codeset_table;
		var $_a_codeset_table;
		var $_pattern_table;
		var $data;
		var $_codeset;
		var $_pattern;

		function barcode128( $data, $code_set = 'C' ) {
			$code_set = strtoupper($code_set);
			if ( $code_set != 'A' && $code_set != 'B' && $code_set != 'C') user_error( 'barcode128 : Codeset \'' . $code_set . '\' not found.', E_USER_ERROR);

			$this->_pattern = array();
			$this->_codeset = strtoupper($code_set);
			$this->data = $data;
			$this->_a_codeset_table = array(
											' '=>0,
											'!'=>1,
											'"'=>2,
											'#'=>3,
											'$'=>4,
											'%'=>5,
											'&'=>6,
											'\''=>7,
											'('=>8,
											')'=>9,
											'*'=>10,
											'+'=>11,
											','=>12,
											'-'=>13,
											'.'=>14,
											'/'=>15,
											'0'=>16,
											'1'=>17,
											'2'=>18,
											'3'=>19,
											'4'=>20,
											'5'=>21,
											'6'=>22,
											'7'=>23,
											'8'=>24,
											'9'=>25,
											':'=>26,
											';'=>27,
											'<'=>28,
											'='=>29,
											'>'=>30,
											'?'=>31,
											'@'=>32,
											'A'=>33,
											'B'=>34,
											'C'=>35,
											'D'=>36,
											'E'=>37,
											'F'=>38,
											'G'=>39,
											'H'=>40,
											'I'=>41,
											'J'=>42,
											'K'=>43,
											'L'=>44,
											'M'=>45,
											'N'=>46,
											'O'=>47,
											'P'=>48,
											'Q'=>49,
											'R'=>50,
											'S'=>51,
											'T'=>52,
											'U'=>53,
											'V'=>54,
											'W'=>55,
											'X'=>56,
											'Y'=>57,
											'Z'=>58,
											'['=>59,
											'\\'=>60,
											']'=>61,
											'^'=>62,
											'_'=>63,
											'Start A'=>103,
											'Start B'=>104,
											'Start C'=>105,
											'Stop'=>106
			);


			$this->_b_codeset_table = array(
											' '=>0,
											'!'=>1,
											'"'=>2,
											'#'=>3,
											'$'=>4,
											'%'=>5,
											'&'=>6,
											'\''=>7,
											'('=>8,
											')'=>9,
											'*'=>10,
											'+'=>11,
											','=>12,
											'-'=>13,
											'.'=>14,
											'/'=>15,
											'0'=>16,
											'1'=>17,
											'2'=>18,
											'3'=>19,
											'4'=>20,
											'5'=>21,
											'6'=>22,
											'7'=>23,
											'8'=>24,
											'9'=>25,
											':'=>26,
											';'=>27,
											'<'=>28,
											'='=>29,
											'>'=>30,
											'?'=>31,
											'@'=>32,
											'A'=>33,
											'B'=>34,
											'C'=>35,
											'D'=>36,
											'E'=>37,
											'F'=>38,
											'G'=>39,
											'H'=>40,
											'I'=>41,
											'J'=>42,
											'K'=>43,
											'L'=>44,
											'M'=>45,
											'N'=>46,
											'O'=>47,
											'P'=>48,
											'Q'=>49,
											'R'=>50,
											'S'=>51,
											'T'=>52,
											'U'=>53,
											'V'=>54,
											'W'=>55,
											'X'=>56,
											'Y'=>57,
											'Z'=>58,
											'['=>59,
											'\\'=>60,
											']'=>61,
											'^'=>62,
											'_'=>63,
											'`'=>64,
											'a'=>65,
											'b'=>66,
											'c'=>67,
											'd'=>68,
											'e'=>69,
											'f'=>70,
											'g'=>71,
											'h'=>72,
											'i'=>73,
											'j'=>74,
											'k'=>75,
											'l'=>76,
											'm'=>77,
											'n'=>78,
											'o'=>79,
											'p'=>80,
											'q'=>81,
											'r'=>82,
											's'=>83,
											't'=>84,
											'u'=>85,
											'v'=>86,
											'w'=>87,
											'x'=>88,
											'y'=>89,
											'z'=>90,
											'{'=>91,
											'|'=>92,
											'}'=>93,
											'~'=>94,
											'Start A'=>103,
											'Start B'=>104,
											'Start C'=>105,
											'Stop'=>106
									);
                                    
			$this->_c_codeset_table = array(
											'00'=>0,
											'01'=>1,
											'02'=>2,
											'03'=>3,
											'04'=>4,
											'05'=>5,
											'06'=>6,
											'07'=>7,
											'08'=>8,
											'09'=>9,
											'10'=>10,
											'11'=>11,
											'12'=>12,
											'13'=>13,
											'14'=>14,
											'15'=>15,
											'16'=>16,
											'17'=>17,
											'18'=>18,
											'19'=>19,
											'20'=>20,
											'21'=>21,
											'22'=>22,
											'23'=>23,
											'24'=>24,
											'25'=>25,
											'26'=>26,
											'27'=>27,
											'28'=>28,
											'29'=>29,
											'30'=>30,
											'31'=>31,
											'32'=>32,
											'33'=>33,
											'34'=>34,
											'35'=>35,
											'36'=>36,
											'37'=>37,
											'38'=>38,
											'39'=>39,
											'40'=>40,
											'41'=>41,
											'42'=>42,
											'43'=>43,
											'44'=>44,
											'45'=>45,
											'46'=>46,
											'47'=>47,
											'48'=>48,
											'49'=>49,
											'50'=>50,
											'51'=>51,
											'52'=>52,
											'53'=>53,
											'54'=>54,
											'55'=>55,
											'56'=>56,
											'57'=>57,
											'58'=>58,
											'59'=>59,
											'60'=>60,
											'61'=>61,
											'62'=>62,
											'63'=>63,
											'64'=>64,
											'65'=>65,
											'66'=>66,
											'67'=>67,
											'68'=>68,
											'69'=>69,
											'70'=>70,
											'71'=>71,
											'72'=>72,
											'73'=>73,
											'74'=>74,
											'75'=>75,
											'76'=>76,
											'77'=>77,
											'78'=>78,
											'79'=>79,
											'80'=>80,
											'81'=>81,
											'82'=>82,
											'83'=>83,
											'84'=>84,
											'85'=>85,
											'86'=>86,
											'87'=>87,
											'88'=>88,
											'89'=>89,
											'90'=>90,
											'91'=>91,
											'92'=>92,
											'93'=>93,
											'94'=>94,
											'95'=>95,
											'96'=>96,
											'97'=>97,
											'98'=>98,
											'99'=>99,
											'Start A'=>103,
											'Start B'=>104,
											'Start C'=>105,
											'Stop'=>106
									);
  
			$this->_pattern_table = array(
											'2 1 2 2 2 2',
											'2 2 2 1 2 2',
											'2 2 2 2 2 1',
											'1 2 1 2 2 3',
											'1 2 1 3 2 2',
											'1 3 1 2 2 2',
											'1 2 2 2 1 3',
											'1 2 2 3 1 2',
											'1 3 2 2 1 2',
											'2 2 1 2 1 3',
											'2 2 1 3 1 2',
											'2 3 1 2 1 2',
											'1 1 2 2 3 2',
											'1 2 2 1 3 2',
											'1 2 2 2 3 1',
											'1 1 3 2 2 2',
											'1 2 3 1 2 2',
											'1 2 3 2 2 1',
											'2 2 3 2 1 1',
											'2 2 1 1 3 2',
											'2 2 1 2 3 1',
											'2 1 3 2 1 2',
											'2 2 3 1 1 2',
											'3 1 2 1 3 1',
											'3 1 1 2 2 2',
											'3 2 1 1 2 2',
											'3 2 1 2 2 1',
											'3 1 2 2 1 2',
											'3 2 2 1 1 2',
											'3 2 2 2 1 1',
											'2 1 2 1 2 3',
											'2 1 2 3 2 1',
											'2 3 2 1 2 1',
											'1 1 1 3 2 3',
											'1 3 1 1 2 3',
											'1 3 1 3 2 1',
											'1 1 2 3 1 3',
											'1 3 2 1 1 3',
											'1 3 2 3 1 1',
											'2 1 1 3 1 3',
											'2 3 1 1 1 3',
											'2 3 1 3 1 1',
											'1 1 2 1 3 3',
											'1 1 2 3 3 1',
											'1 3 2 1 3 1',
											'1 1 3 1 2 3',
											'1 1 3 3 2 1',
											'1 3 3 1 2 1',
											'3 1 3 1 2 1',
											'2 1 1 3 3 1',
											'2 3 1 1 3 1',
											'2 1 3 1 1 3',
											'2 1 3 3 1 1',
											'2 1 3 1 3 1',
											'3 1 1 1 2 3',
											'3 1 1 3 2 1',
											'3 3 1 1 2 1',
											'3 1 2 1 1 3',
											'3 1 2 3 1 1',
											'3 3 2 1 1 1',
											'3 1 4 1 1 1',
											'2 2 1 4 1 1',
											'4 3 1 1 1 1',
											'1 1 1 2 2 4',
											'1 1 1 4 2 2',
											'1 2 1 1 2 4',
											'1 2 1 4 2 1',
											'1 4 1 1 2 2',
											'1 4 1 2 2 1',
											'1 1 2 2 1 4',
											'1 1 2 4 1 2',
											'1 2 2 1 1 4',
											'1 2 2 4 1 1',
											'1 4 2 1 1 2',
											'1 4 2 2 1 1',
											'2 4 1 2 1 1',
											'2 2 1 1 1 4',
											'4 1 3 1 1 1',
											'2 4 1 1 1 2',
											'1 3 4 1 1 1',
											'1 1 1 2 4 2',
											'1 2 1 1 4 2',
											'1 2 1 2 4 1',
											'1 1 4 2 1 2',
											'1 2 4 1 1 2',
											'1 2 4 2 1 1',
											'4 1 1 2 1 2',
											'4 2 1 1 1 2',
											'4 2 1 2 1 1',
											'2 1 2 1 4 1',
											'2 1 4 1 2 1',
											'4 1 2 1 2 1',
											'1 1 1 1 4 3',
											'1 1 1 3 4 1',
											'1 3 1 1 4 1',
											'1 1 4 1 1 3',
											'1 1 4 3 1 1',
											'4 1 1 1 1 3',
											'4 1 1 3 1 1',
											'1 1 3 1 4 1',
											'1 1 4 1 3 1',
											'3 1 1 1 4 1',
											'4 1 1 1 3 1',
											'2 1 1 4 1 2',
											'2 1 1 2 1 4',
											'2 1 1 2 3 2',
											'2 3 3 1 1 1 2'
								);
		} // End of Constructor
	
		function _compute_checkdigit() {
			$codeset_table = $this->{'_'.strtolower($this->_codeset).'_codeset_table'};
			$sum = $codeset_table['Start ' . $this->_codeset];
            if( $this->_codeset == 'C' ) {
                $x = 1;
                for ($i=0;$i<strlen($this->data);$i++) {
                    $sum += ($x)*(int)($this->data[$i].$this->data[($i+1)]);
                    $i++;
                    $x++;
                }
            }
            else {
                for ($i=0;$i<strlen($this->data);$i++) {
                    $sum += ($i+1)*$codeset_table[$this->data[$i]];
                }
            }
			return $sum % 103;
		}	// End of function _compute_checkdigit()

		
		function _compute_pattern() {
			for($i=0;$i<count($this->_pattern);$i++) array_shift($this->_pattern);
			$codeset_table = $this->{'_'.strtolower($this->_codeset).'_codeset_table'};
			$this->_pattern[] = $this->_pattern_table[$codeset_table['Start ' . $this->_codeset]];
            if( $this->_codeset == 'C' ) {
               for ($i=0;$i<strlen($this->data);$i++) {
                    $this->_pattern[] = $this->_pattern_table[$codeset_table[$this->data[$i].$this->data[($i+1)]]];
                    $i++;
                }
            }
            else {
               for ($i=0;$i<strlen($this->data);$i++) {
				$this->_pattern[] = $this->_pattern_table[$codeset_table[$this->data[$i]]];
			}
            }
			
			$this->_pattern[] = $this->_pattern_table[$this->_compute_checkdigit()];
			$this->_pattern[] = $this->_pattern_table[$codeset_table['Stop']];
		} // End of function _compute_pattern()
		

		function get_pattern() {
			return $this->_pattern;
		} // End of function get_pattern()
		
		function _dump_pattern() {
			header('Content-Type: text/plain');
			print_r($this->_pattern);
		}
		
		function get_width( $char_width ) {
			return ceil((strlen($this->data)+5)*$char_width);
		}
		
	} // End of Class
?>