<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class CopyrightController extends Controller
{
    public const HEADER = "
.-----. _            .-----.                .-----.
`-. .-':_;           `-. .-'                `-. .-'
  : :  .-. .--.  _____ : : .--.   .--.  _____ : : .--.  .--.
  : :  : :'  ..':_____:: :' .; ; '  ..':_____:: :' .; :' '_.'
  :_;  :_;`.__.'       :_;`.__,_;`.__.'       :_;`.__.'`.__.'
        ";

    // Generated with  https://patorjk.com/software/taag/
    public const COPYRIGHT = "
.____           __                      _________      .__                        .__        
|    |    __ __|  | _______    ______  /   _____/ ____ |  |__   ____  ______ _____|__| ____  
|    |   |  |  \  |/ /\__  \  /  ___/  \_____  \_/ ___\|  |  \ /  _ \/  ___//  ___/  |/ ___\ 
|    |___|  |  /    <  / __ \_\___ \   /        \  \___|   Y  (  <_> )___ \ \___ \|  / /_/  >
|_______ \____/|__|_ \(____  /____  > /_______  /\___  >___|  /\____/____  >____  >__\___  / 
        \/          \/     \/     \/          \/     \/     \/           \/     \/  /_____/  
        ";

    static public function getCopyright(): string
    {
        return self::HEADER . self::COPYRIGHT;
    }

    static public function showCopyright(): Response
    {
        return response( self::getCopyright() )->header('Content-Type', 'text/plain');
    }
}
