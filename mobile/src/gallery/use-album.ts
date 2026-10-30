import { useQuery } from '@tanstack/react-query';

import { apiFetch } from '@/api/client';
import type { Album } from '@/api/types';

/** One album (GET /gallery/{slug}), shared by the album screen and the photo viewer (one request for both). */
export function useAlbum(slug: string) {
  return useQuery({
    queryKey: ['gallery', 'album', slug],
    queryFn: () => apiFetch<{ data: Album }>(`/gallery/${encodeURIComponent(slug)}`).then((answer) => answer.data),
  });
}
