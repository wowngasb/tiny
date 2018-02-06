<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/3 0003
 * Time: 11:47
 */

namespace Tiny\Plugin;


use Illuminate\Contracts\Events\Dispatcher;
use Tiny\Util;

class DbDispatcher implements Dispatcher
{

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = [];


    /**
     * The event firing stack.
     *
     * @var array
     */
    protected $firing = [];

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array $events
     * @param  mixed $listener
     * @param int $priority
     * @return void
     */
    public function listen($events, $listener, $priority = 0)
    {
        foreach ((array)$events as $event) {
            $this->listeners[$event][] = $listener;
        }
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]);
    }

    /**
     * Register an event and payload to be fired later.
     *
     * @param  string $event
     * @param  array $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        $this->listen($event . '_pushed', function () use ($event, $payload) {
            $this->fire($event, $payload);
        });
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {

    }


    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param  string|object $event
     * @param  array $payload
     * @return mixed
     */
    public function until($event, $payload = [])
    {
        return $this->fire($event, $payload, true);
    }

    /**
     * Flush a set of pushed events.
     *
     * @param  string $event
     * @return void
     */
    public function flush($event)
    {
        $this->fire($event . '_pushed');
    }

    /**
     * Get the event that is currently firing.
     *
     * @return string
     */
    public function firing()
    {
        return last($this->firing);
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @param  bool $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }

        if (!is_array($payload)) {
            $payload = [$payload];
        }

        $this->firing[] = $event;

        $responses = [];
        foreach ($this->getListeners($event) as $listener) {
            $response = call_user_func_array($listener, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (!is_null($response) && $halt) {
                array_pop($this->firing);

                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        array_pop($this->firing);

        return $halt ? null : $responses;
    }


    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        $listener_list = !empty($this->listeners[$eventName]) ? $this->listeners[$eventName] : [];
        if (!empty($this->listeners['*']) && $eventName != '*') {
            $tmp_list = !empty($this->listeners['*']) ? $this->listeners['*'] : [];
            $listener_list = array_merge($listener_list, $tmp_list);
        }

        return $listener_list;
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string $event
     * @return void
     */
    public function forget($event)
    {
        unset($this->listeners[$event]);
    }

    /**
     * Forget all of the pushed listeners.
     *
     * @return void
     */
    public function forgetPushed()
    {
        foreach ($this->listeners as $key => $value) {
            if (Util::str_endwith($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }


}