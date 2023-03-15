import App from "./App";
import { render } from '@wordpress/element';

/**
 * Import the stylesheet for the plugin.
 */
import './style/main.scss';

// Render the App component into the DOM
render(<App endpont={graph_api_end_point.resturl} nonce={graph_api_end_point.nonce} />, document.getElementById('dashboard_chart'));