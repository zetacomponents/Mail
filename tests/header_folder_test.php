<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailHeaderFolderTest extends ezcTestCase
{
    public function testFoldAnyTooShort()
    {
        $reference = "This is a short string";
        $this->assertEquals( $reference,
                             ezcMailHeaderFolder::foldAny( $reference ) );
    }

    public function testFoldAny998()
    {
        $reference = "This is a much much longer string that goes over more than 998 characters. Since that is quite long this string will go on for a long time. Time to put on some music. I can recommend Pink Floyd, Hooverphonic, Chet Baker, Miles Davis, Morcheeba, Micheal Jackson, Madonna, Yanni, Jean Michelle Jarre, D'Sound, 4 Hero and a lot more. Now it is time to just fill this space with random stuff. But before I come to that, let me tell you that you should start flying. Doesn't matter what, but you need to fly. Go paragliding. FLYYYYYYYYYY :) Ok, still reading? You must be completely mad. MAD.. RAVIN MAD. fraardsadsf isadkjfahsdf tdsfjher tyieurwer eweriuer eyadsfifu rydsfiausdf goiusaydfoiuasydfosdaf odsfasdfoy dadskfjhkasjfd thlsakdjfhlksdjf haskdfjh alksjdf hlkasdjf hlksajdf hlaksjdf hldksajf hklsjadh falskjdf haklsjd fhklsajdf hkajlsd fhlkjash dfklajsdf hksjad hfkljsah dflkjash dfkljash dflkjashd fkljasfd hlkajs hdfkljasdh fkljasd hflkjasd hfjklas hdflkjash dfkljas hdfkjashd flkjasdh flaksjfdh aklsjfd hlkasjfd haksjldf hklsadf safdhkl sajkd fhlaksjdh f askdjhfl laksdhf lkasdfsadhflkajs dfhkljhasdl f";
        ezcMailHeaderFolder::setLimit( ezcMailHeaderFolder::HARD_LIMIT );
        $folded = ezcMailHeaderFolder::foldAny( $reference );
        $exploded = explode( ezcMailTools::lineBreak(), $folded );
        $this->assertEquals( 2, count( $exploded  ) );
        $this->assertEquals( 989, strlen( $exploded[0] ) );
    }

    public function testFoldAny998MultiFold()
    {
        $reference = "This is a much much longer string that goes over more than 998 characters. Since that is quite long this string will go on for a long time. Time to put on some music. I can recommend Pink Floyd, Hooverphonic, Chet Baker, Miles Davis, Morcheeba, Micheal Jackson, Madonna, Yanni, Jean Michelle Jarre, D'Sound, 4 Hero and a lot more. Now it is time to just fill this space with random stuff. But before I come to that, let me tell you that you should start flying. Doesn't matter what, but you need to fly. Go paragliding. FLYYYYYYYYYY :) Ok, still reading? You must be completely mad. MAD.. RAVIN MAD. fraardsadsf isadkjfahsdf tdsfjher tyieurwer eweriuer eyadsfifu rydsfiausdf goiusaydfoiuasydfosdaf odsfasdfoy dadskfjhkasjfd thlsakdjfhlksdjf haskdfjh alksjdf hlkasdjf hlksajdf hlaksjdf hldksajf hklsjadh falskjdf haklsjd fhklsajdf hkajlsd fhlkjash dfklajsdf hksjad hfkljsah dflkjash dfkljash dflkjashd fkljasfd hlkajs hdfkljasdh fkljasd hflkjasd hfjklas hdflkjash dfkljas hdfkjashd flkjasdh flaksjfdh aklsjfd hlkasjfd haksjldf hklsadf safdhkl sajkd fhlaksjdh f askdjhfl laksdhf lkasdfsadhflkajs dfhkljhasdl fdsjaflkd asldfk tuiy iuy tuiy tiuyt iuyt iuyt iuy tiuy tuiyt iuyt iuty iuy tiuyt iuyt iuy tiuy tiu ytiuy tiuy tiuy tiut iuyt iut iu tiuyt iuy tiut iuy tuyt iut iuyt  ytiuy tiu ytiuy tiuy tiuy tiuy tiuy tuy tiuy tiuy tiuy tiuy tuytiutyiuyt iuytiuyt iutyiyut iutiuyt uy tiu ytiu ytiu ytiu ytiuy tiuy tuiytiutuitiuyt iuyt ui tuiytiuytiut uiuyt iuyt iutui tuiytuitiytiuy tiuyt uiytiutyiuyti uiyt uituytiuyt uiuyt iuytiuttiuytiuyt uyt uiytuiytiutiuty iuytiutiuytiuyt uytiutiuyt iuyt iuy tuyt iut iuyt iut iuyt uyt uiyt iuyt iuty iuyt iuyt iuyt uiyt yt uit iutyuytyiutuytuitiutiutyut y tuityutuytutuyt ytiuytiuytuiytuyt yitiytiyti iuyty ytiuyt yutiuyt yuti utiu ytiyutu ytiuyt iuyt uytiuyt iuytiu ytiuyt iuytyu tiuyt iutyiuytyuti uytiuytiu ytuiytyiutuy tyutiu ytiuytyut iuytiu t iut uiyt utyi utui ytiuyty iut uiyt uy tiu ytiu tyi uyt iuyt iu ytiu yt iuyt iuyt ui tu ytuiy tiutuyit ituuyti tuy tiuyt iuyt iuytiu tyu tiu ytiu ty uit yt iuytituuit yitu tiut yt iut yut iy ity utiu ytiu y yutityu";
        ezcMailHeaderFolder::setLimit( ezcMailHeaderFolder::HARD_LIMIT );
        $folded = ezcMailHeaderFolder::foldAny( $reference );
        $exploded = explode( ezcMailTools::lineBreak(), $folded );
        $this->assertEquals( 3, count( $exploded  ) );
        $this->assertEquals( 989, strlen( $exploded[0] ) );
        $this->assertEquals( 993, strlen( $exploded[1] ) );
    }

    public function testFoldAny76()
    {
        $reference = "This is a much much longer string that goes over more than 998 characters. Since that is quite long this string will go on for a long time. Time to put on some music. I can recommend Pink Floyd, Hooverphonic, Chet Baker, Miles Davis, Morcheeba, Micheal Jackson, Madonna, Yanni, Jean Michelle Jarre, D'Sound, 4 Hero and a lot more. Now it is time to just fill this space with random stuff. But before I come to that, let me tell you that you should start flying. Doesn't matter what, but you need to fly. Go paragliding. FLYYYYYYYYYY :) Ok, still reading? You must be completely mad. MAD.. RAVIN MAD. fraardsadsf isadkjfahsdf tdsfjher tyieurwer eweriuer eyadsfifu rydsfiausdf goiusaydfoiuasydfosdaf odsfasdfoy dadskfjhkasjfd thlsakdjfhlksdjf haskdfjh alksjdf hlkasdjf hlksajdf hlaksjdf hldksajf hklsjadh falskjdf haklsjd fhklsajdf hkajlsd fhlkjash dfklajsdf hksjad hfkljsah dflkjash dfkljash dflkjashd fkljasfd hlkajs hdfkljasdh fkljasd hflkjasd hfjklas hdflkjash dfkljas hdfkjashd flkjasdh flaksjfdh aklsjfd hlkasjfd haksjldf hklsadf safdhkl sajkd fhlaksjdh f askdjhfl laksdhf lkasdfsadhflkajs dfhkljhasdl f";
        ezcMailHeaderFolder::setLimit( ezcMailHeaderFolder::SOFT_LIMIT );
        $folded = ezcMailHeaderFolder::foldAny( $reference );
        $exploded = explode( ezcMailTools::lineBreak(), $folded );
        foreach ( $exploded as $line )
        {
            $this->assertTrue( strlen( $line ) <= 76 );
        }
    }

    public function testFoldAny76LongWord()
    {
        $reference = "Thisisalongwordthatismorethan76characterslong.Let'sseehowthisishandledbyourlittlefolder That was the first space.";
        ezcMailHeaderFolder::setLimit( ezcMailHeaderFolder::SOFT_LIMIT );
        $folded = ezcMailHeaderFolder::foldAny( $reference );
        $exploded = explode( ezcMailTools::lineBreak(), $folded );
        $this->assertEquals( 2, count( $exploded  ) );
        $this->assertEquals( 87, strlen( $exploded[0] ) );
        $this->assertEquals( 26, strlen( $exploded[1] ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailHeaderFolderTest" );
    }
}
?>
