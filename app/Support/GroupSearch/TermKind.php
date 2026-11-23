<?php

declare(strict_types=1);

namespace App\Support\GroupSearch;

enum TermKind: string
{
    case Word = 'word';
    case Tag = 'tag';
    case Attribute = 'attribute';
    case Admin = 'admin';
}
