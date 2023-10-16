<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Orders\UseCase\Command\Sticker\Sticker;

use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersStickerInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbOrdersSticker */
final class WbStickerDTO implements WbOrdersStickerInterface
{
	
	/**
     * Стикер
     */
    #[Assert\NotBlank]
	private string $sticker;
	
	/**
     * Номер штрихкод заказа
     */
    #[Assert\NotBlank]
	private string $part;
	
	
	/** Стикер */
	public function getSticker() : string
	{
		return $this->sticker;
	}
	
	
	public function setSticker(string $sticker) : void
	{
		$this->sticker = $sticker;
	}
	
	
	/** Номер штрихкод заказа */
	
	public function getPart() : string
	{
		return $this->part;
	}
	
	
	public function setPart(string $part) : void
	{
		$this->part = $part;
	}
	
}

