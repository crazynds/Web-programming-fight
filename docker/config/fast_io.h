#ifndef FAST_IO_H
#define FAST_IO_H

#include <iostream>

struct FastIO
{
    FastIO()
    {
        std::ios::sync_with_stdio(false);
        std::cin.tie(nullptr);
        std::cout.tie(nullptr);
    }
};

static FastIO _____fast_io_instance;

#endif // FAST_IO_H
