import { fireEvent, render, screen } from '@testing-library/react-native';

import { EmptyState, ErrorState, LoadingState } from '@/components/states';
import { ThemedText } from '@/components/themed-text';
import { HeadingMaxFontScale } from '@/constants/theme';

it('shows the API message with a way to try again', async () => {
  const retry = jest.fn();
  await render(<ErrorState message="Can't reach the server." onRetry={retry} />);

  expect(screen.getByRole('alert')).toHaveTextContent("Can't reach the server.");
  await fireEvent.press(screen.getByRole('button', { name: 'Try again' }));
  expect(retry).toHaveBeenCalledTimes(1);
});

it('falls back to a general message when there is none', async () => {
  await render(<ErrorState message={null} onRetry={jest.fn()} />);

  expect(screen.getByText('Something went wrong. Please try again.')).toBeOnTheScreen();
});

it('offers one thing to do on an empty list', async () => {
  const clear = jest.fn();
  await render(<EmptyState message="No hikes match." action={{ title: 'Clear filters', onPress: clear }} />);

  expect(screen.getByText('No hikes match.')).toBeOnTheScreen();
  await fireEvent.press(screen.getByRole('button', { name: 'Clear filters' }));
  expect(clear).toHaveBeenCalledTimes(1);
});

it('names the loading spinner for screen readers', async () => {
  await render(<LoadingState />);

  expect(screen.getByLabelText('Loading')).toBeOnTheScreen();
});

it("follows the phone's font size, with a limit for headings only", async () => {
  await render(
    <>
      <ThemedText type="title">Title</ThemedText>
      <ThemedText>Body</ThemedText>
    </>,
  );

  expect(screen.getByText('Title').props.maxFontSizeMultiplier).toBe(HeadingMaxFontScale);
  expect(screen.getByText('Body').props.maxFontSizeMultiplier).toBeUndefined();
  expect(screen.getByText('Body').props.allowFontScaling).not.toBe(false);
});
