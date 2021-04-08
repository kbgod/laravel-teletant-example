<?php


namespace App\Services;


use App\Models\User;
use App\Utils\TelegramBot\Answer;
use App\Utils\TelegramBot\Scenes;
use App\Utils\TelegramBot\Storage;
use Askoldex\Teletant\Addons\Menux;
use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Exception\TeletantException;
use Askoldex\Teletant\States\Stage;

class TelegramBotService
{
    protected Bot $bot;
    protected UserService $userService;

    public function __construct(Bot $bot, UserService $userService)
    {
        $this->bot = $bot;
        $this->userService = $userService;
    }

    public function boot(): void
    {
        $this->bootMiddlewares();
        $this->bootEvents();
    }

    public function bootStage(): Stage
    {
        $stage = new Stage();
        $stage->addScenes(...Scenes::getAllScenes());
        return $stage;
    }

    public function bootMiddlewares()
    {
        $this->bot->middlewares([
            function (Context $ctx, callable $next) {
                $user = $this->userService->registerTelegramUser($ctx->getUserID());
                $ctx->getContainer()
                    ->singleton(User::class, function () use ($user) {
                    return $user;
                });

                $next($ctx);
            },
            function (Context $ctx, callable $next) {
                /** @var User $user */
                $user = $ctx->getContainer()->get(User::class);
                $storage = new Storage($user);
                $ctx->setStorage($storage);

                $next($ctx);
            },
            $this->bootStage()->middleware()
        ]);
    }

    public function bootEvents()
    {
        $this->bot->onCommand('start', function (Context $ctx, User $user) {
            $menu = Menux::Create('Главное меню');
            $menu->btn(__('bot.pages.games'));
            $ctx->replyHTML('Hello, ' . $user->telegram_id, $menu);
        });

        $this->bot->onText(__('bot.pages.games'), function (Context $ctx) {
            $ctx->replyHTML('Вы перешли в раздел игры');
        });

        $this->bot->onCommand('admin', Answer::permissionDenied());

        $this->bot->onCommand('enter', function (Context $ctx) {
            $ctx->enter('hello');
        });
    }

    /**
     * @throws TeletantException
     */
    public function polling()
    {
        $this->bot->polling();
    }

    /**
     * @throws TeletantException
     */
    public function listen()
    {
        $this->bot->listen();
    }
}
