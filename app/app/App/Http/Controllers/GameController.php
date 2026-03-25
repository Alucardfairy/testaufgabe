<?php

namespace App\Http\Controllers;

use Components\Enums\GameMark;
use Components\Enums\GamePlayer;
use Components\GameBoard\GameBoard;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class GameController extends Controller
{

    /**
     * @param GameBoard $game
     * @return Response
     * @throws Exception
     */
    protected function status_output( GameBoard $game ): Response {

        // Generate a status text for the end of the game.
        $winner = $this->whoHasWon( $game );
        if ( $this->someoneHasWon( $game ) && !$winner )
            $final = "\nSomeone has won the game!";
        elseif ( $this->someoneHasWon( $game ) && $winner === GamePlayer::Human)
            $final = "\nYou have won the game! Congratulations!";
        elseif ( $this->someoneHasWon( $game ) && $winner === GamePlayer::Robot)
            $final = "\nThe bot has won the game...";
        elseif ( !$game->spaceIsLeft() )
            $final = "\nIt's a draw!";
        else $final = '';

        return response(CopyrightController::getCopyright() . "\n\n{$game->draw()}{$final}")->header('Content-Type', 'text/plain');
    }

    /**
     * @param GameBoard $game
     * @return bool
     * @throws Exception
     */
    protected function someoneHasWon( GameBoard $game ): bool {
        
        for ($i=0;$i<=$game::TTT_SIZE-1;$i++) {
            $won = $game->getRow($i)->getSpace(0) !== GameMark::None;
            for ($j=1;$j<=$game::TTT_SIZE-1;$j++) {
                if ($game->getRow($i)->getSpace(0) !== $game->getRow($i)->getSpace($j)) {
                    $won=false;
                    break;
                }
            }
            if ($won)
                return $won;

            $won = $game->getColumn($i)->getSpace(0) !== GameMark::None;
            for ($j=1;$j<=$game::TTT_SIZE-1;$j++) {
                if ($game->getColumn($i)->getSpace(0) !== $game->getColumn($i)->getSpace($j)) {
                    $won=false;
                    break;
                }
            }
            if ($won)
                return $won;
        }

        $won = $game->getMainDiagonal(0)->getSpace(0) !== GameMark::None;
        for ($j=1;$j<=$game::TTT_SIZE-1;$j++) {
            if ($game->getMainDiagonal(0)->getSpace(0) !== $game->getMainDiagonal(0)->getSpace($j)) {
                $won=false;
                break;
            }
        }

        if ($won)
            return $won;

        $won = $game->getAntiDiagonal(0)->getSpace(0) !== GameMark::None;
        for ($j=1;$j<=$game::TTT_SIZE-1;$j++) {
            if ($game->getAntiDiagonal(0)->getSpace(0) !== $game->getAntiDiagonal(0)->getSpace($j)) {
                $won=false;
                break;
            }
        }
        return $won;
    }

    protected function whoHasWon( GameBoard $game ): ?GamePlayer {

        if($this->someoneHasWon($game))
            return $game->getLastPlayer();

        return null;
    }

    /**
     * Is the given player allowed to take the next turn?
     * @param GameBoard $game
     * @param GamePlayer $player
     * @return bool
     */
    protected function isAllowedToPlay( GameBoard $game, GamePlayer $player) : bool {

        if($game->getLastPlayer()===$player)
            return false;
        else 
            return true;
    }

    /**
     * @param int $x The x position entered by the player
     * @param int $y The y position entered by the player
     * @return Response
     * @throws Exception
     */
    public function play(int $x, int $y): Response
    {
        // Loading the game board
        $game = GameBoard::load();

        // Check if the given position is actually valid; can't have the player draw a cross on the table next to the
        // game board ;)
        if ($x < 0 || $x > $game::TTT_SIZE-1 || $y < 0 || $y > $game::TTT_SIZE-1)
            return response("Position outside of the game board")->setStatusCode(422)->header('Content-Type', 'text/plain');

        // Prevent the player from playing if the game has already ended
        if ($this->someoneHasWon( $game ) || !$game->spaceIsLeft())
            return response("You are not allowed to play. The game has already ended.")->setStatusCode(403)->header('Content-Type', 'text/plain');

        // Prevent the player from playing if it is not his turn
        if (!$this->isAllowedToPlay($game, GamePlayer::Human))
            return response("You are not allowed to play. It is the bots turn!")->setStatusCode(403)->header('Content-Type', 'text/plain');

        if($game->getSpace($x, $y) !== GameMark::None)
            return response("This space has already been claimed!")->setStatusCode(403)->header('Content-Type', 'text/plain');
        $game->setSpace($x, $y,GameMark::Circle);

        $game->save();
        return $this->status_output( $game );
    }

    /**
     * The MÜNSMEDIA GmbH bot plays one turn
     * @return Response
     * @throws Exception
     */
    public function playBot(): Response
    {
        // Load the current game board
        $game = GameBoard::load();

        // ##### TASK 5 - Understand the bot ###########################################################################
        // =============================================================================================================
        // This first step to beat your enemy is to thoroughly understand them.
        // Luckily, as a developer, you can literally look into its head. So, check out the bot logic and try to
        // understand what it does.
        // =============================================================================================================

        // Prevent the bot from playing if the game has already ended
        if ($this->someoneHasWon( $game ) || !$game->spaceIsLeft())
            return response("Bot is not allowed to play. The game has already ended.")->setStatusCode(403)->header('Content-Type', 'text/plain');

        // is the bot really allowed to play?
        if (!$this->isAllowedToPlay($game, GamePlayer::Robot))
            return response("Bot is not allowed to play. It is your turn!")->setStatusCode(403)->header('Content-Type', 'text/plain');

        $freeSpaces = [];

        // get all rows of our game board
        foreach ($game->getRows() as $y => $row) {
            // get all spaces inside the row
            foreach ($row->getSpaces() as $x => $space) {
                // check whether the space is still free
                if ($space->free()) {
                    // save the free space to our free spaces array
                    $freeSpaces[] = ['x' => $x, 'y' => $y];
                }
            }
        }

        // get random free space from our array - https://laravel.com/docs/9.x/helpers#method-array-random
        $randomFreeSpaceXY = Arr::random($freeSpaces);

        // mark field with a cross
        $game->setSpace($randomFreeSpaceXY['x'], $randomFreeSpaceXY['y'], GameMark::Cross);

        // save changed game board
        $game->save();

        return $this->status_output($game);
    }

    /**
     * Displays the board
     * @return Response
     */
    public function display(): Response
    {
        // Load the current game  and displays it
        return $this->status_output( GameBoard::load() );
    }

    /**
     * Resets the board
     * @return Response
     */
    public function reset(): Response
    {
        // Load the current game board
        $game = GameBoard::load();
        $game->clear();
        $game->save();

        return $this->status_output( $game );
    }
}
