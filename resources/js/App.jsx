import * as React from 'react'
import { createRoot } from 'react-dom/client'
import { LaravelReactI18nProvider } from 'laravel-react-i18n'

import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Provider } from 'react-redux'
import { createStore, applyMiddleware } from 'redux'
import reduxThunk from 'redux-thunk'
import { ProSidebarProvider } from 'react-pro-sidebar';

import SideLayout from './components/SideLayout'
import Login from './pages/Login'
import Traders from './pages/Traders'
import Routing from './pages/Routing'
import Clipboard from './pages/Clipboard'
import Users from './pages/Users'
import ChangePassword from './pages/ChangePassword';

import Preloading from './components/Preloading'
import ToastMsg from './components/ToastMsg'

import NonProtectedRoute from './utils/NonProtectedRoute';
import ProtectedRoute from './utils/ProtectedRoute';
import AdminRoute from './utils/AdminRoute';

import reducers from './reducers/index'
import reportWebVitals from './reportWebVitals'
import './index.scss'

import { createTheme, ThemeProvider } from '@mui/material/styles';
import { jaJP } from '@mui/material/locale';

const theme = createTheme(
  {
    palette: {
      primary: { main: '#1976d2' },
    },
  },
  jaJP,
);

const createStoreWithMiddleware = applyMiddleware(reduxThunk)(createStore)
const store = createStoreWithMiddleware(reducers)

export default function App(){
	return(
		<Router>
      <Routes>
        <Route exact path="/" element={<NonProtectedRoute><Login /></NonProtectedRoute>} />
        <Route element={<SideLayout/>}>
          <Route path="/traders" element={<ProtectedRoute><Traders /></ProtectedRoute>} />
          <Route path="/routing" element={<AdminRoute><Routing /></AdminRoute>} />
          <Route path="/clipboard" element={<AdminRoute><Clipboard /></AdminRoute>} />
          <Route path="/users" element={<AdminRoute><Users /></AdminRoute>} />
          <Route path="/changePassword" element={<ProtectedRoute><ChangePassword /></ProtectedRoute>} />
        </Route>
      </Routes>
      <Preloading />
      <ToastMsg />
    </Router>
	);
}

const el = document.getElementById('root');

createRoot(el).render(
  <LaravelReactI18nProvider
      lang={'ja'}
      fallbackLang={'en'}
      resolve={lang => import(`../../lang/php_${lang}.json`)
      // resolve={async (lang) => {
      // const langs = import.meta.glob('../../lang/*.json')
      // const fn = langs[`/lang/php_${lang}.json`];
      
      // if (typeof fn === 'function') {
      //     return await fn();
      // }}
  }>
    <React.StrictMode>
      <Provider store={store}>
        <ProSidebarProvider>
          <ThemeProvider theme={theme}>
            <App />
          </ThemeProvider>
        </ProSidebarProvider>
      </Provider>
    </React.StrictMode>
  </LaravelReactI18nProvider>
)

reportWebVitals();