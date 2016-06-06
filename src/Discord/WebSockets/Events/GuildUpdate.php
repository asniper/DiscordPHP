<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $deferred->notify('Guild is unavailable.');

            return;
        }

        $guildPart = $this->factory->create(Guild::class, $data, true);

        $roles = new RoleRepository(
            $this->http,
            $this->cache,
            $this->factory
        );

        foreach ($data->roles as $role) {
            $rolePart = $this->factory->create(Role::class, $role, true);

            $this->cache->set("guild.{$guildPart->id}.roles.{$rolePart->id}", $rolePart);
            $roles->push($rolePart);
        }

        $guildPart->roles = $roles;

        if ($guildPart->large) {
            $this->discord->addLargeGuild($guildPart);
        }

        $this->cache->set("guilds.{$guildPart->id}", $guildPart);
        $this->discord->guilds->push($guildPart);

        $deferred->resolve($guildPart);
    }
}
