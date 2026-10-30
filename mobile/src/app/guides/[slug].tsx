import { useQuery } from '@tanstack/react-query';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { ActivityIndicator, Linking, Pressable, Share, StyleSheet } from 'react-native';
import { WebView, type WebViewNavigation } from 'react-native-webview';

import { ApiError, apiFetch } from '@/api/client';
import type { Article } from '@/api/types';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { articleHtml } from '@/guides/article-html';
import { useTheme } from '@/hooks/use-theme';
import { appRouteFor } from '@/links';
import { strings } from '@/strings';

/**
 * One guide article (GET /guides/{slug}). The CMS body is HTML, so the article is shown in a web view; a tapped link
 * opens the matching app screen, or the browser. Only published articles: others answer 404.
 */
export default function GuideScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const theme = useTheme();
  const { data: article, error, isPending, refetch } = useQuery({
    queryKey: ['guides', 'detail', slug],
    queryFn: () => apiFetch<{ data: Article }>(`/guides/${encodeURIComponent(slug)}`).then((answer) => answer.data),
  });

  // The page itself loads from the HTML string, reported as the base URL (or about:blank / data:), and "#heading"
  // links stay on it; every other navigation is a tapped link.
  const onNavigate = ({ url }: WebViewNavigation) => {
    const page = article?.web_url ?? '';
    if (url.startsWith('about:') || url.startsWith('data:') || url === page || url.startsWith(`${page}#`)) return true;

    const route = appRouteFor(url);
    if (route) router.push(route);
    else void Linking.openURL(url);

    return false;
  };

  const notFound = error instanceof ApiError && error.status === 404;

  return (
    <>
      <Stack.Screen
        options={{
          title: article?.title ?? '',
          headerRight: () =>
            article ? (
              <Pressable
                accessibilityRole="button"
                accessibilityLabel={strings.hike.share}
                onPress={() => void Share.share({ message: `${article.title}\n${article.web_url}` })}
                hitSlop={Spacing.two}>
                <ThemedText type="linkPrimary">{strings.hike.share}</ThemedText>
              </Pressable>
            ) : null,
        }}
      />

      {isPending ? (
        <ThemedView style={styles.center}>
          <ActivityIndicator />
        </ThemedView>
      ) : error ? (
        <ThemedView style={styles.center}>
          <ThemedText style={styles.text}>{notFound ? strings.guides.notFound : error.message}</ThemedText>
          {!notFound ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
        </ThemedView>
      ) : (
        <WebView
          originWhitelist={['*']}
          // baseUrl: images and links written as "/storage/..." in the CMS resolve on the website.
          source={{ html: articleHtml(article, theme), baseUrl: article.web_url }}
          onShouldStartLoadWithRequest={onNavigate}
          style={{ backgroundColor: theme.background }}
        />
      )}
    </>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
