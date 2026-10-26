import React from 'react'
import { Link } from '@inertiajs/react';
import { IconChevronRight, IconChevronLeft } from '@tabler/icons-react';
export default function Pagination({ links }) {

    const base = 'inline-flex items-center justify-center min-w-[32px] h-8 px-2 text-sm border rounded-md transition-colors'
    const idle = 'bg-white text-gray-500 border-gray-200 hover:bg-gray-100 dark:bg-gray-950 dark:text-gray-400 dark:hover:bg-gray-900 dark:border-gray-800'
    const active = 'bg-primary-500 text-white border-primary-500 font-semibold shadow-sm dark:bg-primary-500 dark:text-white dark:border-primary-500'

    return (
        <>
            <ul className="mt-4 lg:mt-5 justify-end flex flex-wrap items-center gap-1">
                {links.map((item, i) => {
                    return item.url != null ? (
                        item.label.includes('Previous') ? (
                            <Link className={`${base} ${idle}`} key={i} href={item.url} aria-label="Sebelumnya">
                                <IconChevronLeft size={'18'} strokeWidth={'1.5'} />
                            </Link>
                        ) : item.label.includes('Next') ? (
                            <Link className={`${base} ${idle}`} key={i} href={item.url} aria-label="Berikutnya">
                                <IconChevronRight size={'18'} strokeWidth={'1.5'} />
                            </Link>
                        ) : (
                            <Link
                                className={`${base} ${item.active ? active : idle}`}
                                key={i}
                                href={item.url}
                                aria-current={item.active ? 'page' : undefined}
                            >
                                {item.label}
                            </Link>
                        )
                    ) : null;
                })}
            </ul>
        </>
    )
}
