import { NativeTabs } from 'expo-router/native-tabs';

import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/**
 * The 5 tabs (Android's tab bar holds 5 at most). Icons are system symbols: Material Symbols on Android, SF Symbols
 * on iOS, so they follow the phone's style and need no image files. The Organizer screen lives in More.
 */
export default function AppTabs() {
  const theme = useTheme();

  return (
    <NativeTabs
      backgroundColor={theme.bar}
      indicatorColor={theme.backgroundSelected}
      iconColor={{ default: theme.textSecondary, selected: theme.accent }}
      labelStyle={{ default: { color: theme.textSecondary }, selected: { color: theme.accent } }}>
      <NativeTabs.Trigger name="index">
        <NativeTabs.Trigger.Label>{strings.tabs.home}</NativeTabs.Trigger.Label>
        <NativeTabs.Trigger.Icon md="home" sf={{ default: 'house', selected: 'house.fill' }} />
      </NativeTabs.Trigger>

      <NativeTabs.Trigger name="hikes">
        <NativeTabs.Trigger.Label>{strings.tabs.hikes}</NativeTabs.Trigger.Label>
        <NativeTabs.Trigger.Icon md="hiking" sf="figure.hiking" />
      </NativeTabs.Trigger>

      <NativeTabs.Trigger name="shop">
        <NativeTabs.Trigger.Label>{strings.tabs.shop}</NativeTabs.Trigger.Label>
        <NativeTabs.Trigger.Icon md="shopping_bag" sf={{ default: 'bag', selected: 'bag.fill' }} />
      </NativeTabs.Trigger>

      <NativeTabs.Trigger name="more">
        <NativeTabs.Trigger.Label>{strings.tabs.more}</NativeTabs.Trigger.Label>
        <NativeTabs.Trigger.Icon md="menu" sf="line.3.horizontal" />
      </NativeTabs.Trigger>

      <NativeTabs.Trigger name="account">
        <NativeTabs.Trigger.Label>{strings.tabs.account}</NativeTabs.Trigger.Label>
        <NativeTabs.Trigger.Icon md="account_circle" sf={{ default: 'person.crop.circle', selected: 'person.crop.circle.fill' }} />
      </NativeTabs.Trigger>
    </NativeTabs>
  );
}
