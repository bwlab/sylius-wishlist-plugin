<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Resolver;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistInterface;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;

/**
 * Lists of the CURRENT owner (logged customer, or guest identified by the bwlab_wishlist cookie) in the current channel.
 * Phase 1 methods (find, getOrCreate) work on the DEFAULT list.
 */
interface CurrentWishlistResolverInterface
{
    /** Request attribute holding the resolved DEFAULT wishlist (read by the cookie listener). */
    public const REQUEST_ATTRIBUTE = '_bwlab_wishlist';

    /** Existing DEFAULT list of the current owner. Never creates. */
    public function find(): ?WishlistInterface;

    /** Same as find(), or a new not-yet-persisted default list: persist it with the repository after adding the first item. */
    public function getOrCreate(): WishlistInterface;

    /** @return list<WishlistInterface> all lists of the current owner, default first, then by creation date. Never creates. */
    public function findAll(): array;

    /** The list with this id IF it belongs to the current owner, else null: callers answer 404 for both "not mine" and "missing". */
    public function findOwned(int $id): ?WishlistInterface;

    /** Destination of an add: $listId given → that owned list or null (404); null → the default list (getOrCreate, maybe not persisted). */
    public function getTarget(?int $listId = null): ?WishlistInterface;

    /** List to preselect in the PDP/PLP menu: the most recently changed list (updatedAt), else the default (getOrCreate). */
    public function getLastUsed(): WishlistInterface;

    /**
     * Creates and PERSISTS a named list (and the default list first, if the owner has none).
     *
     * @throws WishlistNameException blank, longer than 60, duplicate (case-insensitive), or already 20 lists
     */
    public function create(string $name): WishlistInterface;

    /**
     * Renames and PERSISTS an owned list (the default one too). Items untouched.
     *
     * @throws WishlistNameException
     */
    public function rename(WishlistInterface $wishlist, string $name): void;

    /**
     * Deletes an owned list with its items. Catalogue and other lists untouched.
     *
     * @throws WishlistOperationException when $wishlist is the default list
     */
    public function delete(WishlistInterface $wishlist): void;
}
