<?php

namespace App;

enum WipeStatus: int
{
    case ACTIVE = 1;

    case WIPED = 2;

    case WIPING = 3;

    case UNKNOWN = 4;

}
