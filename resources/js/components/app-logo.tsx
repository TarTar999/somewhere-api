import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <div className="flex items-center gap-1 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:w-full">
            <div className="flex aspect-square size-8 items-center justify-center rounded-xl overflow-hidden flex-shrink-0 group-data-[collapsible=icon]:size-10">
                <AppLogoIcon className="size-8 object-cover group-data-[collapsible=icon]:size-10" />
            </div>
            <div className="grid flex-1 text-left min-w-0 group-data-[collapsible=icon]:!hidden">
                <span className="truncate leading-tight font-bold text-gray-900 text-sm">
                    SomeWhere App
                </span>
            </div>
        </div>
    );
}
