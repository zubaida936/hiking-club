import { File, Paths } from 'expo-file-system';
import * as Sharing from 'expo-sharing';

/**
 * Shares a club photo through the phone's share sheet, where people can also save it (Google Photos, Files...).
 * The photo is downloaded to the app's cache first (sharing needs a file on the phone); a photo shared before is
 * reused. No photo library permission is needed.
 */
export async function sharePhoto(photo: { id: number; url: string }, title: string): Promise<void> {
  const extension = photo.url.match(/\.(jpe?g|png|webp)(?:\?|$)/i)?.[1]?.toLowerCase() ?? 'jpg';
  const file = new File(Paths.cache, `club-photo-${photo.id}.${extension}`);
  if (!file.exists) await File.downloadFileAsync(photo.url, file);

  await Sharing.shareAsync(file.uri, { mimeType: extension === 'png' ? 'image/png' : extension === 'webp' ? 'image/webp' : 'image/jpeg', dialogTitle: title });
}
