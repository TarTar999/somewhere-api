declare module 'react-leaflet-draw' {
    import { FC } from 'react';
    import { ControlPosition } from 'leaflet';

    interface DrawControlProps {
        position?: ControlPosition;
        onCreated?: (e: any) => void;
        onEdited?: (e: any) => void;
        onDeleted?: (e: any) => void;
        onDrawStart?: (e: any) => void;
        onDrawStop?: (e: any) => void;
        onEditStart?: (e: any) => void;
        onEditStop?: (e: any) => void;
        onDeleteStart?: (e: any) => void;
        onDeleteStop?: (e: any) => void;
        draw?: {
            polygon?: boolean | object;
            polyline?: boolean | object;
            rectangle?: boolean | object;
            circle?: boolean | object;
            marker?: boolean | object;
            circlemarker?: boolean | object;
        };
        edit?: {
            featureGroup?: L.FeatureGroup;
            remove?: boolean;
            edit?: boolean | object;
        };
    }

    export const EditControl: FC<DrawControlProps>;
}

declare module 'leaflet-draw' {
    // This module is used for side effects (importing CSS and extending Leaflet)
}
